<?php

namespace App\Services\Scheduling;

use App\Enums\ApprovalStatus;
use App\Enums\AvailabilityType;
use App\Enums\BookingStatus;
use App\Enums\JabatanInternal;
use App\Enums\ScheduleRole;
use App\Enums\ScheduleStatus;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\ScheduleConflictException;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\SystemParameter;
use App\Models\TravelTime;
use App\Models\User;
use App\Models\VendorAvailabilityRequest;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Algoritma Deteksi Bentrok Estafet MUA Multi-Lokasi & Kapasitas Kru.
 *
 * Penugasan baru i bentrok dengan penugasan j (sumber daya sama) bila:
 *   s(i) < e(j) + t(l(j), l(i))  AND  s(j) < e(i) + t(l(i), l(j))
 * dengan t = waktu tempuh + waktu persiapan antarlokasi (tabel travel_times).
 */
class ConflictDetectionService
{
    private const FIELDS = [
        'booking_id', 'user_id', 'vendor_service_id', 'location_id', 'peran', 'is_tenaga_lepas',
        'nama_tenaga_lepas', 'phone_tenaga_lepas', 'tanggal', 'waktu_mulai', 'waktu_selesai', 'catatan',
    ];

    /** @var array<string, int> */
    private array $gapCache = [];

    /**
     * @param  array<string, mixed>  $data  booking_id, user_id?, peran, location_id, waktu_mulai, waktu_selesai, tanggal?, is_tenaga_lepas?
     */
    public function check(array $data, ?Schedule $ignore = null): ConflictResult
    {
        $peran = $data['peran'] instanceof ScheduleRole ? $data['peran'] : ScheduleRole::from($data['peran']);
        $start = Carbon::parse($data['waktu_mulai']);
        $end = Carbon::parse($data['waktu_selesai']);

        if ($end->lte($start)) {
            throw new BusinessRuleException('Waktu selesai harus setelah waktu mulai.', 'jadwal');
        }

        $tanggal = Carbon::parse($data['tanggal'] ?? $start)->startOfDay();
        $locationId = (int) $data['location_id'];
        $userId = $data['user_id'] ?? null;
        $conflicts = [];

        if ($userId) {
            $conflicts = array_merge(
                $this->timeConflicts((int) $userId, $peran, $start, $end, $locationId, $ignore),
                $this->availabilityConflicts((int) $userId, $tanggal, $start, $end),
            );
        }

        if ($peran->countsTowardDecorCapacity() && empty($data['is_tenaga_lepas'])) {
            if ($conflict = $this->capacityConflict($tanggal, $userId ? (int) $userId : null, (int) $data['booking_id'], $ignore)) {
                $conflicts[] = $conflict;
            }
        }

        return new ConflictResult($conflicts);
    }

    /**
     * Simpan penugasan. Bentrok hanya bisa disimpan bila Owner/Admin mengisi Alasan Override (tercatat di audit log).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ScheduleConflictException
     */
    public function assign(array $data, User $by, ?string $alasanOverride = null, ?Schedule $schedule = null): Schedule
    {
        $result = $this->check($data, $schedule);
        $alasan = trim((string) $alasanOverride);

        if ($result->hasConflict()) {
            if ($alasan === '') {
                throw new ScheduleConflictException($result);
            }
            if (! $by->isOwner()) {
                throw new BusinessRuleException('Hanya Owner/Admin yang dapat melakukan override jadwal bentrok.', 'override');
            }
        }

        return DB::transaction(function () use ($data, $by, $alasan, $result, $schedule) {
            $start = Carbon::parse($data['waktu_mulai']);
            $attrs = array_merge(Arr::only($data, self::FIELDS), [
                'tanggal' => Carbon::parse($data['tanggal'] ?? $start)->toDateString(),
                'is_override' => $result->hasConflict(),
                'alasan_override' => $result->hasConflict() ? $alasan : null,
                'override_by' => $result->hasConflict() ? $by->id : null,
                'override_at' => $result->hasConflict() ? now() : null,
            ]);

            if ($schedule) {
                $schedule->update($attrs);
            } else {
                $schedule = Schedule::create($attrs + ['created_by' => $by->id, 'status' => ScheduleStatus::Dijadwalkan]);
            }

            if ($result->hasConflict()) {
                AuditLog::record('schedule.override', $schedule, null, ['konflik' => $result->toArray()], "Override bentrok. Alasan: {$alasan}", $by->id);
            }

            return $schedule;
        });
    }

    /**
     * Validasi ulang seluruh penugasan pada satu tanggal (penugasan ber-override diabaikan).
     *
     * @return array<int, ConflictResult> schedule_id => hasil
     */
    public function auditDay(CarbonInterface $tanggal): array
    {
        $found = [];
        $this->activeSchedules()->whereDate('tanggal', $tanggal->toDateString())->where('is_override', false)->get()
            ->each(function (Schedule $s) use (&$found) {
                $result = $this->check($s->only(['booking_id', 'user_id', 'location_id', 'peran', 'is_tenaga_lepas', 'tanggal', 'waktu_mulai', 'waktu_selesai']), $s);
                if ($result->hasConflict()) {
                    $found[$s->id] = $result;
                }
            });

        return $found;
    }

    /** Jeda minimum t(from, to) dalam menit: waktu tempuh + persiapan. */
    public function gapMinutes(int $fromLocationId, int $toLocationId): int
    {
        if ($fromLocationId === $toLocationId) {
            return (int) SystemParameter::get('waktu_tempuh_lokasi_sama_menit', 15);
        }

        return $this->gapCache["{$fromLocationId}-{$toLocationId}"] ??= ($tt = TravelTime::where('from_location_id', $fromLocationId)
            ->where('to_location_id', $toLocationId)->first())
            ? $tt->totalMenit()
            : (int) SystemParameter::get('waktu_tempuh_default_menit', 60) + (int) SystemParameter::get('waktu_persiapan_default_menit', 30);
    }

    /** @return list<Conflict> */
    private function timeConflicts(int $userId, ScheduleRole $peran, Carbon $start, Carbon $end, int $locationId, ?Schedule $ignore, bool $withSuggestions = true): array
    {
        $conflicts = [];
        $lokasiBaru = Location::find($locationId)?->nama ?? 'lokasi baru';

        $others = $this->activeSchedules($ignore)
            ->where('user_id', $userId)
            ->where('waktu_mulai', '<', $end->copy()->addDay())
            ->where('waktu_selesai', '>', $start->copy()->subDay())
            ->with(['location', 'booking', 'user'])
            ->orderBy('waktu_mulai')
            ->get();

        foreach ($others as $j) {
            $tJi = $this->gapMinutes($j->location_id, $locationId);
            $tIj = $this->gapMinutes($locationId, $j->location_id);

            $bentrok = $start->lt($j->waktu_selesai->copy()->addMinutes($tJi))
                && $j->waktu_mulai->lt($end->copy()->addMinutes($tIj));

            if (! $bentrok) {
                continue;
            }

            $suggestions = [];
            if ($withSuggestions) {
                $suggestions[] = $j->waktu_mulai->lte($start)
                    ? 'Geser waktu mulai menjadi paling cepat '.$this->fmt($j->waktu_selesai->copy()->addMinutes($tJi)).'.'
                    : 'Selesaikan paling lambat '.$this->fmt($j->waktu_mulai->copy()->subMinutes($tIj))." agar sempat ke {$j->location->nama}.";
            }

            $conflicts[] = new Conflict(
                Conflict::WAKTU,
                sprintf(
                    '%s sudah bertugas di %s (%s) pukul %s–%s. Dibutuhkan jeda %d menit (perjalanan + persiapan) ke %s.',
                    $j->user->name, $j->location->nama, $j->booking->kode_booking,
                    $j->waktu_mulai->format('H:i'), $j->waktu_selesai->format('H:i'),
                    $j->waktu_mulai->lte($start) ? $tJi : $tIj, $lokasiBaru,
                ),
                $suggestions,
                $j->id,
            );
        }

        if ($conflicts && $withSuggestions) {
            foreach ($this->availableAlternatives($peran, $userId, $start, $end, $locationId, $ignore) as $alt) {
                $conflicts[0] = new Conflict(
                    $conflicts[0]->type, $conflicts[0]->message,
                    [...$conflicts[0]->suggestions, "Tugaskan {$alt->name} yang tersedia pada waktu tersebut."],
                    $conflicts[0]->scheduleId,
                );
            }
        }

        return $conflicts;
    }

    /** @return Collection<int, User> */
    private function availableAlternatives(ScheduleRole $peran, int $excludeUserId, Carbon $start, Carbon $end, int $locationId, ?Schedule $ignore): Collection
    {
        $jabatan = JabatanInternal::tryFrom($peran->value);
        if (! $jabatan) {
            return collect();
        }

        return User::where('jabatan', $jabatan->value)->where('is_active', true)->whereKeyNot($excludeUserId)->get()
            ->filter(fn (User $u) => $this->timeConflicts($u->id, $peran, $start, $end, $locationId, $ignore, false) === [])
            ->take(3)
            ->values();
    }

    /** @return list<Conflict> */
    private function availabilityConflicts(int $userId, Carbon $tanggal, Carbon $start, Carbon $end): array
    {
        return VendorAvailabilityRequest::with('vendor')
            ->where('vendor_user_id', $userId)
            ->whereDate('tanggal', $tanggal->toDateString())
            ->where('status', ApprovalStatus::Disetujui->value)
            ->where('ketersediaan', AvailabilityType::TidakTersedia->value)
            ->get()
            ->filter(function (VendorAvailabilityRequest $r) use ($tanggal, $start, $end) {
                if (! $r->jam_mulai || ! $r->jam_selesai) {
                    return true;
                }

                return $start->lt($tanggal->copy()->setTimeFromTimeString($r->jam_selesai))
                    && $tanggal->copy()->setTimeFromTimeString($r->jam_mulai)->lt($end);
            })
            ->map(fn (VendorAvailabilityRequest $r) => new Conflict(
                Conflict::KETERSEDIAAN,
                "{$r->vendor->name} sudah menyatakan tidak tersedia pada {$this->fmtDate($tanggal)} (disetujui Admin).",
                ['Pilih vendor lain atau hubungi vendor untuk konfirmasi ulang.'],
            ))
            ->values()
            ->all();
    }

    private function capacityConflict(Carbon $tanggal, ?int $userId, int $bookingId, ?Schedule $ignore): ?Conflict
    {
        $kapasitas = (int) SystemParameter::get('kapasitas_kru_dekorasi', 5);
        $terjadwal = $this->decorCrewOn($tanggal, $ignore);
        $total = $terjadwal->count() + ($userId && $terjadwal->contains($userId) ? 0 : 1);

        if ($total <= $kapasitas) {
            return null;
        }

        $suggestions = [];
        $acara = Booking::find($bookingId)?->tanggal_acara;
        if ($acara) {
            foreach ([2, 1] as $h) {
                $alt = $acara->copy()->subDays($h);
                if ($alt->isSameDay($tanggal) || $alt->lt(Carbon::today())) {
                    continue;
                }
                $sisa = $kapasitas - $this->decorCrewOn($alt, $ignore)->count();
                if ($sisa > 0) {
                    $suggestions[] = "Sarankan pemasangan dekorasi berjenjang pada H-{$h} ({$this->fmtDate($alt)}), sisa kapasitas {$sisa} kru.";
                }
            }
        }
        $suggestions[] = 'Tambah tenaga lepas sebanyak '.($total - $kapasitas).' orang (dicatat sebagai tenaga lepas di jadwal).';

        return new Conflict(
            Conflict::KAPASITAS,
            "Kapasitas kru dekorasi pada {$this->fmtDate($tanggal)} terlampaui: {$total} dari maksimal {$kapasitas} kru internal.",
            $suggestions,
        );
    }

    /** @return Collection<int, int> user_id kru dekorasi internal yang bertugas pada tanggal tsb. */
    private function decorCrewOn(CarbonInterface $tanggal, ?Schedule $ignore): Collection
    {
        return $this->activeSchedules($ignore)
            ->whereDate('tanggal', $tanggal->toDateString())
            ->whereIn('peran', [ScheduleRole::Dekorator->value, ScheduleRole::KruDekorasi->value])
            ->where('is_tenaga_lepas', false)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
    }

    private function activeSchedules(?Schedule $ignore = null): Builder
    {
        return Schedule::query()
            ->where('status', '!=', ScheduleStatus::Dibatalkan->value)
            ->whereHas('booking', fn (Builder $q) => $q->where('status', '!=', BookingStatus::BatalRefund->value))
            ->when($ignore?->exists, fn (Builder $q) => $q->whereKeyNot($ignore->id));
    }

    private function fmt(CarbonInterface $t): string
    {
        return $t->copy()->locale('id')->translatedFormat('H:i (j M)');
    }

    private function fmtDate(CarbonInterface $t): string
    {
        return $t->copy()->locale('id')->translatedFormat('l, j F Y');
    }
}
