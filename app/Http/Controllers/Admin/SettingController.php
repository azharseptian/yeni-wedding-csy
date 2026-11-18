<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\SystemParameter;
use App\Models\TravelTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings', [
            'parameters' => SystemParameter::orderBy('grup')->orderBy('id')->get(),
            'locations' => Location::orderBy('kota')->get(),
            'travelTimes' => TravelTime::with(['from', 'to'])->whereColumn('from_location_id', '<', 'to_location_id')->get()
                ->sortBy(fn ($t) => $t->from->nama.$t->to->nama),
        ]);
    }

    public function updateParameters(Request $request): RedirectResponse
    {
        $values = $request->validate(['parameter' => ['required', 'array'], 'parameter.*' => ['nullable', 'string', 'max:2000']])['parameter'];

        foreach (SystemParameter::whereIn('key', array_keys($values))->get() as $param) {
            $value = $values[$param->key];
            if (in_array($param->tipe, ['integer', 'decimal'], true) && ! is_numeric($value)) {
                return back()->with('error', "{$param->label} harus berupa angka.");
            }
            if ($param->tipe === 'json' && json_decode((string) $value) === null) {
                return back()->with('error', "{$param->label} harus berformat JSON yang valid.");
            }
            $param->update(['value' => $value]);
        }

        return back()->with('success', 'Parameter sistem disimpan.');
    }

    /** Waktu tempuh disimpan simetris (A→B = B→A). */
    public function updateTravelTimes(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_location_id' => ['required', 'exists:locations,id'],
            'to_location_id' => ['required', 'exists:locations,id', 'different:from_location_id'],
            'waktu_tempuh_menit' => ['required', 'integer', 'min:0', 'max:1440'],
            'waktu_persiapan_menit' => ['required', 'integer', 'min:0', 'max:600'],
        ]);

        foreach ([[$data['from_location_id'], $data['to_location_id']], [$data['to_location_id'], $data['from_location_id']]] as [$from, $to]) {
            TravelTime::updateOrCreate(
                ['from_location_id' => $from, 'to_location_id' => $to],
                ['waktu_tempuh_menit' => $data['waktu_tempuh_menit'], 'waktu_persiapan_menit' => $data['waktu_persiapan_menit']],
            );
        }

        return back()->with('success', 'Waktu tempuh & persiapan disimpan.');
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        Location::create($request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kota' => ['required', 'string', 'max:100'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ]));

        return back()->with('success', 'Lokasi ditambahkan. Atur waktu tempuhnya agar deteksi bentrok akurat.');
    }
}
