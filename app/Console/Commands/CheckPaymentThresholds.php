<?php

namespace App\Console\Commands;

use App\Services\Payment\PaymentStatusService;
use App\Support\Rupiah;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('booking:cek-ambang {--tanggal= : Tanggal acuan (Y-m-d), bawaan hari ini}')]
#[Description('Cek ambang pelunasan 80% pada H-7 dan tampilkan tautan pengingat WhatsApp')]
class CheckPaymentThresholds extends Command
{
    public function handle(PaymentStatusService $service): int
    {
        $today = $this->option('tanggal') ? Carbon::parse($this->option('tanggal')) : Carbon::today();
        $belum = $service->evaluateThresholds($today);

        if ($belum->isEmpty()) {
            $this->info('Semua pesanan pada/lewat H-7 sudah memenuhi ambang pelunasan.');

            return self::SUCCESS;
        }

        $this->warn("{$belum->count()} pesanan Belum Memenuhi Ambang:");
        $this->table(['Kode', 'Pasangan', 'Tanggal Acara', 'Dibayar', 'Kekurangan', 'Tautan WA'], $belum->map(fn ($b) => [
            $b->kode_booking,
            $b->client->namaPasangan(),
            $b->tanggal_acara->toDateString(),
            $b->persentaseDibayar().'%',
            Rupiah::format($service->shortfallToThreshold($b)),
            $service->reminderLink($b),
        ])->all());

        return self::SUCCESS;
    }
}
