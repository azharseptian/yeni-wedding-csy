{{-- Pilihan komponen berfluktuasi & layanan vendor. $showVendorCost hanya true untuk Owner. --}}
@php($showVendorCost = $showVendorCost ?? false)
@php($current = $current ?? collect())
<div class="space-y-5">
    <div>
        <h3 class="font-semibold">Komponen Tambahan <span class="text-sm font-normal text-stone-500">(isi jumlah; kosongkan bila tidak dipilih)</span></h3>
        <div class="mt-2 divide-y divide-stone-100 rounded-lg border border-stone-200">
            @foreach ($components as $c)
                @php($cur = $current->first(fn ($i) => $i->item_type === \App\Enums\SnapshotItemType::KomponenDinamis && $i->source_id === $c->id))
                <div class="grid items-center gap-2 p-3 sm:grid-cols-[1fr_auto_auto]">
                    <div>
                        <p class="font-medium">{{ $c->nama }}</p>
                        <p class="text-xs text-stone-500">
                            @if ($c->tipe_harga === \App\Enums\PriceType::PerCm)
                                {{ rupiah($c->harga_satuan) }}/cm · ukuran {{ $c->ukuran_min_cm }}–{{ $c->ukuran_max_cm }} cm
                            @else
                                {{ rupiah($c->harga_satuan) }}/{{ $c->satuan }}
                            @endif
                        </p>
                    </div>
                    <input type="hidden" name="komponen[{{ $c->id }}][component_id]" value="{{ $c->id }}">
                    <label class="text-xs text-stone-500">Jumlah
                        <input class="input w-24" type="number" step="1" min="0" name="komponen[{{ $c->id }}][kuantitas]" value="{{ old("komponen.{$c->id}.kuantitas", $cur ? (float) $cur->kuantitas : '') }}">
                    </label>
                    @if ($c->tipe_harga === \App\Enums\PriceType::PerCm)
                        <label class="text-xs text-stone-500">Ukuran (cm)
                            <input class="input w-28" type="number" min="{{ $c->ukuran_min_cm }}" max="{{ $c->ukuran_max_cm }}" name="komponen[{{ $c->id }}][ukuran_cm]" value="{{ old("komponen.{$c->id}.ukuran_cm", $cur?->ukuran_cm) }}">
                        </label>
                    @else
                        <span></span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    <div>
        <h3 class="font-semibold">Layanan Vendor Keluarga</h3>
        <div class="mt-2 divide-y divide-stone-100 rounded-lg border border-stone-200">
            @foreach ($services as $s)
                @php($cur = $current->first(fn ($i) => $i->item_type === \App\Enums\SnapshotItemType::LayananVendor && $i->source_id === $s->id))
                <div class="grid items-center gap-2 p-3 sm:grid-cols-[1fr_auto]">
                    <div>
                        <p class="font-medium">{{ $s->nama_layanan }}</p>
                        <p class="text-xs text-stone-500">
                            {{ rupiah($s->tarif_klien) }}/{{ $s->satuan }}
                            @if ($showVendorCost)
                                · <span class="text-amber-700">tarif vendor {{ rupiah($s->tarif_vendor) }} · margin {{ rupiah($s->margin) }}</span> · {{ $s->vendor?->name }}
                            @endif
                        </p>
                    </div>
                    <input type="hidden" name="vendor[{{ $s->id }}][vendor_service_id]" value="{{ $s->id }}">
                    <label class="text-xs text-stone-500">Jumlah ({{ $s->satuan }})
                        <input class="input w-28" type="number" min="0" name="vendor[{{ $s->id }}][kuantitas]" value="{{ old("vendor.{$s->id}.kuantitas", $cur ? (float) $cur->kuantitas : '') }}">
                    </label>
                </div>
            @endforeach
        </div>
    </div>
</div>
