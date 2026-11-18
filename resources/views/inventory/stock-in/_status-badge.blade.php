@php
    $statusDetails = [
        'pending_approval' => ['Menunggu PIC', 'bg-amber-50 text-amber-800 border-amber-200', 'schedule'],
        'approved' => ['Disetujui', 'bg-emerald-50 text-emerald-700 border-emerald-200', 'check_circle'],
        'rejected' => ['Ditolak', 'bg-red-50 text-red-700 border-red-200', 'cancel'],
    ];
    [$statusLabel, $statusClass, $statusIcon] = $statusDetails[$receipt->status] ?? ['Tercatat', 'bg-gray-50 text-gray-700 border-gray-200', 'info'];
@endphp
<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold border {{ $statusClass }}">
    <span class="material-symbols-outlined text-[13px]">{{ $statusIcon }}</span>{{ $statusLabel }}
</span>
