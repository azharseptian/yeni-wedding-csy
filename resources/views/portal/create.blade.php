<x-layout title="Pesan Paket">
    <h1 class="text-2xl font-bold">Formulir Pemesanan</h1>
    <form method="POST" action="{{ route('portal.pesan.store') }}" class="mt-4 grid gap-6 lg:grid-cols-3">
        @csrf
        <div class="space-y-6 lg:col-span-2">
            <div class="card"><h2 class="mb-3 font-bold">Detail Acara</h2>@include('partials.event-fields')</div>
            <div class="card">@include('partials.item-fields')</div>
        </div>
        <div>
            <div class="card sticky top-4 space-y-3 text-sm">
                <h2 class="font-bold">Ketentuan</h2>
                <ul class="list-inside list-disc space-y-1 text-stone-600">
                    <li>DP fleksibel, berapa pun di atas Rp0.</li>
                    <li>Tanggal & harga dikunci setelah DP diverifikasi.</li>
                    <li>Pembayaran minimal {{ $ambang }}% paling lambat H-{{ $batasHari }}. Pemesanan di H-{{ $batasHari }} atau lebih dekat wajib langsung membayar {{ $ambang }}%.</li>
                    <li>Bila batal, DP dikembalikan 100%.</li>
                </ul>
                <button class="btn-primary w-full py-3">Kirim Pesanan</button>
            </div>
        </div>
    </form>
</x-layout>
