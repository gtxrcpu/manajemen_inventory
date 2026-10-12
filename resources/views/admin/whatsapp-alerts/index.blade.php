@extends('layouts.dashboard')
@php($pageTitle = 'Konfigurasi Alert WhatsApp')

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <!-- Header -->
    <div>
        <h1 class="font-poppins text-xl font-semibold text-gray-900">Alert WhatsApp</h1>
        <p class="mt-1 text-xs text-gray-600">Konfigurasi penerima dan koneksi notifikasi stok.</p>
    </div>

    @if(session('status'))
    <div class="rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-800 flex items-start gap-2">
        <span class="material-symbols-outlined text-[18px] text-green-600 shrink-0">check_circle</span>
        <span>{{ session('status') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="rounded-xl bg-red-50 border border-red-200 p-3 text-sm text-red-700 flex items-start gap-2">
        <span class="material-symbols-outlined text-[18px] text-red-600 shrink-0">error</span>
        <span>{{ $errors->first() }}</span>
    </div>
    @endif

    <!-- Connection Status Card -->
    <section class="rounded-xl border border-gray-200 bg-white p-4">
        <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-green-600 text-[20px]">{{ ($connection['connected'] ?? false) ? 'check_circle' : 'sync' }}</span>
            <h2 class="font-semibold text-gray-900">Status WhatsApp</h2>
        </div>

        <div class="flex items-center gap-2 p-3 rounded-lg {{ ($connection['connected'] ?? false) ? 'bg-green-50 border border-green-200' : 'bg-amber-50 border border-amber-200' }}">
            <span class="material-symbols-outlined text-[18px] {{ ($connection['connected'] ?? false) ? 'text-green-700' : 'text-amber-700' }}">
                {{ ($connection['connected'] ?? false) ? 'wifi' : 'wifi_off' }}
            </span>
            <p data-whatsapp-connection class="text-sm font-medium {{ ($connection['connected'] ?? false) ? 'text-green-700' : 'text-amber-700' }}">
                {{ ($connection['connected'] ?? false) ? 'Terhubung'.(!empty($connection['account']) ? ' · '.$connection['account'] : '') : 'Belum terhubung' }}
            </p>
        </div>

        <div data-whatsapp-qr-wrap class="mt-4 {{ $qr ? '' : 'hidden' }}">
            <div class="flex items-start gap-2 p-2.5 bg-blue-50 border border-blue-200 rounded-lg mb-3">
                <span class="material-symbols-outlined text-blue-600 text-[18px] shrink-0">info</span>
                <div class="text-xs text-blue-700 leading-relaxed">
                    <p class="font-semibold mb-1">Cara menghubungkan:</p>
                    <ol class="list-decimal pl-4 space-y-0.5">
                        <li>Buka WhatsApp di HP yang akan jadi pengirim</li>
                        <li>Ketuk menu (titik tiga) → Perangkat tertaut</li>
                        <li>Ketuk "Tautkan perangkat"</li>
                        <li>Arahkan kamera ke QR code di bawah</li>
                    </ol>
                </div>
            </div>
            <div class="flex justify-center">
                <img data-whatsapp-qr src="{{ $qr ?? '' }}" alt="QR untuk menghubungkan WhatsApp" class="h-56 w-56 rounded-xl border-2 border-gray-200 p-2 bg-white shadow-sm">
            </div>
        </div>

        @if(!empty($connection['error']))
        <div class="flex items-start gap-2 p-2.5 bg-red-50 border border-red-200 rounded-lg mt-3">
            <span class="material-symbols-outlined text-red-600 text-[18px] shrink-0">error</span>
            <p class="text-xs text-red-700">{{ $connection['error'] }}</p>
        </div>
        @endif
    </section>

    <!-- Configuration Form -->
    <form method="POST" action="{{ route('admin.whatsapp-alerts.update') }}" class="space-y-4 rounded-xl border border-gray-200 bg-white p-4">
        @csrf @method('PUT')

        <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
            <span class="material-symbols-outlined text-gray-600 text-[20px]">settings</span>
            <h2 class="font-semibold text-gray-900">Pengaturan</h2>
        </div>

        <label class="block">
            <div class="flex items-center gap-1.5 mb-1.5">
                <span class="material-symbols-outlined text-gray-600 text-[18px]">contacts</span>
                <span class="text-sm font-semibold text-gray-900">Daftar nomor yang akan diberi tahu</span>
            </div>
            <textarea name="recipients" rows="4" placeholder="6281234567890&#10;6281234567891" class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-gray-400 focus:ring-0">{{ old('recipients', implode("\n", $settings->recipients ?? [])) }}</textarea>
            <span class="flex items-start gap-1.5 mt-1.5 text-xs text-gray-500">
                <span class="material-symbols-outlined text-[14px] shrink-0">info</span>
                <span>Tulis satu nomor per baris. Mulai dengan 62 untuk Indonesia (contoh: 6281234567890)</span>
            </span>
        </label>

        <label class="block">
            <div class="flex items-center gap-1.5 mb-1.5">
                <span class="material-symbols-outlined text-gray-600 text-[18px]">message</span>
                <span class="text-sm font-semibold text-gray-900">Isi pesan otomatis</span>
            </div>
            <textarea required name="message_template" rows="5" maxlength="1000" class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-gray-400 focus:ring-0">{{ old('message_template', $settings->message_template ?: "🔔 *Peringatan Stok Minimum*\n\nProduk: *{product}*\nLokasi: {location}\nStok saat ini: *{stock} {unit}*\nBatas minimum: {minimum} {unit}\n\nMohon periksa dan lakukan pengadaan bila diperlukan.") }}</textarea>
            <div class="flex items-start gap-1.5 mt-1.5 p-2 bg-gray-50 rounded-lg">
                <span class="material-symbols-outlined text-gray-600 text-[14px] shrink-0">lightbulb</span>
                <div class="text-xs text-gray-600">
                    <p class="font-medium mb-1">Variabel yang bisa dipakai:</p>
                    <div class="space-y-0.5">
                        <p><code class="px-1 py-0.5 bg-gray-200 rounded text-xs">{product}</code> = Nama barang</p>
                        <p><code class="px-1 py-0.5 bg-gray-200 rounded text-xs">{location}</code> = Nama lokasi gudang</p>
                        <p><code class="px-1 py-0.5 bg-gray-200 rounded text-xs">{stock}</code> = Jumlah stok tersisa</p>
                        <p><code class="px-1 py-0.5 bg-gray-200 rounded text-xs">{minimum}</code> = Batas minimum yang diatur</p>
                    </div>
                </div>
            </div>
        </label>

        <div class="rounded-lg border-2 border-amber-300 bg-amber-50 p-3">
            <div class="flex items-start gap-2 mb-2">
                <span class="material-symbols-outlined text-amber-700 text-[18px] shrink-0">info</span>
                <b class="text-xs font-semibold text-amber-900">Penting untuk diketahui</b>
            </div>
            <p class="text-xs leading-relaxed text-amber-900 pl-7">Sistem ini menggunakan WhatsApp Web (bukan aplikasi resmi dari WhatsApp). Sebaiknya gunakan nomor bisnis khusus, jangan nomor pribadi. Untuk keamanan maksimal, pertimbangkan pakai layanan resmi WhatsApp Business API.</p>
        </div>

        <label class="flex items-start gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <input type="checkbox" name="risk_accepted" value="1" @checked(old('risk_accepted', (bool) $settings->risk_accepted_at)) class="mt-0.5 rounded border-gray-300">
            <span class="text-xs leading-relaxed text-gray-700">Saya paham risikonya dan akan pakai nomor khusus untuk bisnis (bukan nomor pribadi)</span>
        </label>

        <label class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings->enabled)) class="rounded border-gray-300">
            <span class="text-sm font-semibold text-gray-900">Nyalakan notifikasi WhatsApp</span>
        </label>

        <button class="w-full rounded-xl bg-gray-900 px-4 py-3 text-sm font-semibold text-white flex items-center justify-center gap-2 active:scale-[0.99] transition-transform">
            <span class="material-symbols-outlined text-[18px]">save</span>
            <span>Simpan pengaturan</span>
        </button>
    </form>

    <!-- Delivery History -->
    <section class="space-y-3">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-gray-600 text-[20px]">history</span>
            <h2 class="font-semibold text-gray-900">Riwayat pesan</h2>
        </div>
        <p class="text-xs text-gray-500 -mt-2">Catatan pesan yang berhasil terkirim atau gagal</p>

        @forelse($logs as $log)
        <article class="rounded-xl border border-gray-200 bg-white p-3">
            <div class="flex items-start justify-between gap-2 mb-2">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $log->alert->product->name }}</p>
                    <div class="flex items-center gap-1.5 text-xs text-gray-600 mt-0.5">
                        <span class="material-symbols-outlined text-[14px]">location_on</span>
                        <span>{{ $log->alert->location->name }}</span>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide shrink-0 
                    {{ $log->status === 'sent' ? 'bg-green-100 text-green-700' : '' }}
                    {{ $log->status === 'queued' ? 'bg-blue-100 text-blue-700' : '' }}
                    {{ $log->status === 'sending' ? 'bg-amber-100 text-amber-700' : '' }}
                    {{ $log->status === 'failed' ? 'bg-red-100 text-red-700' : '' }}">
                    {{ ['sent'=>'Terkirim','queued'=>'Menunggu kirim','sending'=>'Sedang dikirim','failed'=>'Tidak terkirim'][$log->status] ?? $log->status }}
                </span>
            </div>

            <div class="flex items-center gap-3 text-xs text-gray-600 pb-2 border-b border-gray-100">
                <div class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">phone_iphone</span>
                    <span>{{ $log->recipient }}</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                    <span>{{ ($log->sent_at ?? $log->last_attempt_at ?? $log->created_at)->timezone('Asia/Jakarta')->format('d M H:i') }} WIB</span>
                </div>
            </div>

            @if($log->error_message)
            <div class="flex items-start gap-1.5 mt-2 p-2 bg-red-50 rounded-lg">
                <span class="material-symbols-outlined text-red-600 text-[14px] shrink-0">error</span>
                <p class="text-xs text-red-700 break-words">{{ $log->friendly_error }}</p>
            </div>
            @elseif($log->status === 'sent')
            <div class="mt-2 rounded-lg bg-green-50 p-2 text-xs text-green-700">Pesan sudah diterima WhatsApp untuk dikirim.</div>
            @elseif($log->status === 'queued')
            <div class="mt-2 rounded-lg bg-blue-50 p-2 text-xs text-blue-700">{{ $log->friendly_error ?? 'Pesan sedang menunggu giliran untuk dikirim.' }}</div>
            @endif
        </article>
        @empty
        <div class="rounded-xl border border-gray-200 bg-white p-6 text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-gray-400 text-[24px]">mail</span>
            </div>
            <p class="text-sm text-gray-500">Belum ada pesan yang dikirim</p>
        </div>
        @endforelse

        {{ $logs->links() }}
    </section>
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-6xl space-y-6">
    <!-- Header -->
    <div>
        <h1 class="font-poppins text-2xl font-semibold text-gray-900">Alert WhatsApp</h1>
        <p class="mt-1 text-sm text-gray-500">Atur koneksi, daftar penerima, dan pesan alert stok minimum.</p>
    </div>

    @if(session('status'))
    <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-800 flex items-start gap-2">
        <span class="material-symbols-outlined text-[20px] text-green-600 shrink-0">check_circle</span>
        <span>{{ session('status') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 flex items-start gap-2">
        <span class="material-symbols-outlined text-[20px] text-red-600 shrink-0">error</span>
        <span>{{ $errors->first() }}</span>
    </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Connection Status Card -->
        <section class="rounded-xl border border-gray-200 bg-white p-5">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-green-600 text-[22px]">{{ ($connection['connected'] ?? false) ? 'check_circle' : 'sync' }}</span>
                <h2 class="font-semibold text-gray-900">Status WhatsApp</h2>
            </div>

            <div class="flex items-center gap-2.5 p-4 rounded-lg {{ ($connection['connected'] ?? false) ? 'bg-green-50 border border-green-200' : 'bg-amber-50 border border-amber-200' }}">
                <div class="w-10 h-10 rounded-full flex items-center justify-center {{ ($connection['connected'] ?? false) ? 'bg-green-100' : 'bg-amber-100' }}">
                    <span class="material-symbols-outlined text-[20px] {{ ($connection['connected'] ?? false) ? 'text-green-700' : 'text-amber-700' }}">
                        {{ ($connection['connected'] ?? false) ? 'wifi' : 'wifi_off' }}
                    </span>
                </div>
                <div>
                    <p data-whatsapp-connection class="text-sm font-semibold {{ ($connection['connected'] ?? false) ? 'text-green-700' : 'text-amber-700' }}">
                        {{ ($connection['connected'] ?? false) ? 'Terhubung'.(!empty($connection['account']) ? ' · '.$connection['account'] : '') : 'Belum terhubung' }}
                    </p>
                    <p class="text-xs text-gray-600 mt-0.5">Sistem kirim pesan otomatis</p>
                </div>
            </div>

            <div data-whatsapp-qr-wrap class="mt-5 {{ $qr ? '' : 'hidden' }}">
                <div class="flex items-start gap-2 p-3 bg-blue-50 border border-blue-200 rounded-lg mb-4">
                    <span class="material-symbols-outlined text-blue-600 text-[18px] shrink-0">info</span>
                    <div class="text-xs text-blue-700 leading-relaxed">
                        <p class="font-semibold mb-1.5">Cara menghubungkan:</p>
                        <ol class="list-decimal pl-4 space-y-0.5">
                            <li>Buka WhatsApp di HP yang akan jadi pengirim</li>
                            <li>Ketuk menu (titik tiga) → Perangkat tertaut</li>
                            <li>Ketuk "Tautkan perangkat"</li>
                            <li>Arahkan kamera ke QR code di bawah</li>
                        </ol>
                    </div>
                </div>
                <div class="flex justify-center">
                    <div class="p-4 bg-white border-2 border-gray-200 rounded-xl shadow-sm">
                        <img data-whatsapp-qr src="{{ $qr ?? '' }}" alt="QR untuk menghubungkan WhatsApp" class="h-64 w-64">
                    </div>
                </div>
            </div>

            @if(!empty($connection['error']))
            <div class="flex items-start gap-2 p-3 bg-red-50 border border-red-200 rounded-lg mt-4">
                <span class="material-symbols-outlined text-red-600 text-[18px] shrink-0">error</span>
                <p class="text-xs text-red-700">{{ $connection['error'] }}</p>
            </div>
            @endif
        </section>

        <!-- Configuration Form -->
        <form method="POST" action="{{ route('admin.whatsapp-alerts.update') }}" class="space-y-5 rounded-xl border border-gray-200 bg-white p-5">
            @csrf @method('PUT')

            <div class="flex items-center gap-2 pb-4 border-b border-gray-200">
                <span class="material-symbols-outlined text-gray-600 text-[22px]">settings</span>
                <h2 class="font-semibold text-gray-900">Pengaturan</h2>
            </div>

            <label class="block">
                <div class="flex items-center gap-1.5 mb-2">
                    <span class="material-symbols-outlined text-gray-600 text-[18px]">contacts</span>
                    <span class="text-sm font-semibold text-gray-900">Daftar nomor yang akan diberi tahu</span>
                </div>
                <textarea name="recipients" rows="4" placeholder="6281234567890&#10;6281234567891&#10;6281234567892" class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-gray-400 focus:ring-0 font-mono">{{ old('recipients', implode("\n", $settings->recipients ?? [])) }}</textarea>
                <div class="flex items-start gap-1.5 mt-2 text-xs text-gray-500">
                    <span class="material-symbols-outlined text-[14px] shrink-0">info</span>
                    <span>Tulis satu nomor per baris. Mulai dengan 62 untuk Indonesia (contoh: 6281234567890)</span>
                </div>
            </label>

            <label class="block">
                <div class="flex items-center gap-1.5 mb-2">
                    <span class="material-symbols-outlined text-gray-600 text-[18px]">message</span>
                    <span class="text-sm font-semibold text-gray-900">Isi pesan otomatis</span>
                </div>
                <textarea required name="message_template" rows="5" maxlength="1000" class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-gray-400 focus:ring-0">{{ old('message_template', $settings->message_template ?: "🔔 *Peringatan Stok Minimum*\n\nProduk: *{product}*\nLokasi: {location}\nStok saat ini: *{stock} {unit}*\nBatas minimum: {minimum} {unit}\n\nMohon periksa dan lakukan pengadaan bila diperlukan.") }}</textarea>
                <div class="flex items-start gap-1.5 mt-2 p-2.5 bg-gray-50 rounded-lg border border-gray-200">
                    <span class="material-symbols-outlined text-gray-600 text-[16px] shrink-0">lightbulb</span>
                    <div class="text-xs text-gray-600">
                        <p class="font-medium mb-1.5">Variabel yang bisa dipakai dalam pesan:</p>
                        <div class="space-y-1">
                            <p><code class="px-1.5 py-0.5 bg-gray-200 rounded text-xs">{product}</code> = Nama barang</p>
                            <p><code class="px-1.5 py-0.5 bg-gray-200 rounded text-xs">{location}</code> = Nama lokasi gudang</p>
                            <p><code class="px-1.5 py-0.5 bg-gray-200 rounded text-xs">{stock}</code> = Jumlah stok tersisa</p>
                            <p><code class="px-1.5 py-0.5 bg-gray-200 rounded text-xs">{minimum}</code> = Batas minimum yang diatur</p>
                        </div>
                    </div>
                </div>
            </label>

            <div class="rounded-lg border-2 border-amber-300 bg-amber-50 p-4">
                <div class="flex items-start gap-2 mb-2">
                    <span class="material-symbols-outlined text-amber-700 text-[20px] shrink-0">info</span>
                    <b class="text-sm font-semibold text-amber-900">Penting untuk diketahui</b>
                </div>
                <p class="text-xs leading-relaxed text-amber-900 pl-7">Sistem ini menggunakan WhatsApp Web (bukan aplikasi resmi dari WhatsApp). Sebaiknya gunakan nomor bisnis khusus, jangan nomor pribadi. Untuk keamanan maksimal, pertimbangkan pakai layanan resmi WhatsApp Business API.</p>
            </div>

            <label class="flex items-start gap-2.5 p-3 bg-gray-50 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-100 transition-colors">
                <input type="checkbox" name="risk_accepted" value="1" @checked(old('risk_accepted', (bool) $settings->risk_accepted_at)) class="mt-0.5 rounded border-gray-300">
                <span class="text-xs leading-relaxed text-gray-700">Saya paham risikonya dan akan pakai nomor khusus untuk bisnis (bukan nomor pribadi)</span>
            </label>

            <label class="flex items-center gap-2.5 p-3 bg-gray-50 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-100 transition-colors">
                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings->enabled)) class="rounded border-gray-300">
                <span class="text-sm font-semibold text-gray-900">Nyalakan notifikasi WhatsApp</span>
            </label>

            <button class="w-full rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white hover:bg-gray-800 transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>Simpan pengaturan</span>
            </button>
        </form>
    </div>

    <!-- Delivery History Table -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-200 p-5">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-gray-600 text-[22px]">history</span>
                <h2 class="font-semibold text-gray-900">Riwayat pesan</h2>
            </div>
            <p class="mt-1 text-xs text-gray-500">Catatan pesan yang berhasil terkirim atau gagal</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="p-4 font-semibold">Alert</th>
                        <th class="p-4 font-semibold">Penerima</th>
                        <th class="p-4 font-semibold">Status</th>
                        <th class="p-4 font-semibold">Waktu</th>
                        <th class="p-4 font-semibold">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-4">
                            <p class="font-semibold text-gray-900">{{ $log->alert->product->name }}</p>
                            <div class="flex items-center gap-1 text-xs text-gray-500 mt-0.5">
                                <span class="material-symbols-outlined text-[14px]">location_on</span>
                                <span>{{ $log->alert->location->name }}</span>
                            </div>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-gray-400 text-[16px]">phone_iphone</span>
                                <span class="text-gray-700 font-mono text-xs">{{ $log->recipient }}</span>
                            </div>
                        </td>
                        <td class="p-4">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold uppercase tracking-wide
                                {{ $log->status === 'sent' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $log->status === 'queued' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $log->status === 'sending' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $log->status === 'failed' ? 'bg-red-100 text-red-700' : '' }}">
                                <span class="material-symbols-outlined text-[12px]">
                                    {{ $log->status === 'sent' ? 'check_circle' : '' }}
                                    {{ $log->status === 'queued' ? 'schedule' : '' }}
                                    {{ $log->status === 'sending' ? 'sync' : '' }}
                                    {{ $log->status === 'failed' ? 'error' : '' }}
                                </span>
                                {{ ['sent'=>'Terkirim','queued'=>'Menunggu kirim','sending'=>'Sedang dikirim','failed'=>'Tidak terkirim'][$log->status] ?? $log->status }}
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-1.5 text-gray-600">
                                <span class="material-symbols-outlined text-[16px]">schedule</span>
                                <span>{{ ($log->sent_at ?? $log->last_attempt_at ?? $log->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</span>
                            </div>
                        </td>
                        <td class="p-4 max-w-xs">
                            @if($log->error_message)
                            <div class="flex items-start gap-1.5 p-2 bg-red-50 rounded-lg">
                                <span class="material-symbols-outlined text-red-600 text-[14px] shrink-0">error</span>
                                <p class="text-xs text-red-700 break-words">{{ $log->friendly_error }}</p>
                            </div>
                            @elseif($log->status === 'sent')
                            <span class="text-sm text-green-700">Pesan sudah diterima WhatsApp untuk dikirim.</span>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                <span class="material-symbols-outlined text-gray-400 text-[32px]">mail</span>
                            </div>
                            <p class="text-gray-500">Belum ada pesan yang dikirim</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4">
            {{ $logs->links() }}
        </div>
    </div>
</section>
@endsection

@section('page-scripts')
(() => {
    const endpoint = @js(route('admin.whatsapp-alerts.status'));
    let refreshing = false;
    async function refreshWhatsAppStatus() {
        if (refreshing || document.visibilityState === 'hidden') return;
        refreshing = true;
        try {
            const response = await fetch(endpoint, { headers: { 'Accept': 'application/json' }, cache: 'no-store', signal: AbortSignal.timeout(3500) });
            if (!response.ok) return;
            const data = await response.json();
            document.querySelectorAll('[data-whatsapp-qr-wrap]').forEach(wrapper => {
                wrapper.classList.toggle('hidden', !data.qr);
            });
            if (data.qr) {
                document.querySelectorAll('[data-whatsapp-qr]').forEach(image => {
                    if (image.getAttribute('src') !== data.qr) image.src = data.qr;
                });
            }
            document.querySelectorAll('[data-whatsapp-connection]').forEach(status => {
                const states = {
                    qr: 'QR baru siap dipindai',
                    authenticated: 'Perangkat tertaut, menunggu WhatsApp siap',
                    generating_qr: 'Membuat QR baru…',
                    reconnecting: 'Menyambungkan ulang ke WhatsApp…',
                };
                status.textContent = data.connection?.connected
                    ? `Terhubung${data.connection.account ? ` · ${data.connection.account}` : ''}`
                    : (data.qr ? 'QR baru siap dipindai' : (states[data.connection?.state] || 'Menunggu koneksi WhatsApp'));
                status.classList.toggle('text-green-700', Boolean(data.connection?.connected));
                status.classList.toggle('text-amber-700', !data.connection?.connected);
            });
        } catch (_) { /* keep the most recent QR/status visible if the service is temporarily unreachable */ }
        finally { refreshing = false; }
    }
    refreshWhatsAppStatus();
    window.setInterval(refreshWhatsAppStatus, 8000);
})();
@endsection
