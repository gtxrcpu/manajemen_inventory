@if($canReviewPurchaseRequest && $purchaseRequest->status === 'pending_approval')
    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5" aria-label="Keputusan persetujuan Purchase Request">
        @if($errors->any())
            <div class="mb-3 rounded-xl border border-rose-200 bg-white p-3 text-xs text-rose-800">{{ $errors->first() }}</div>
        @endif
        <div class="flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-amber-700"><span class="material-symbols-outlined">fact_check</span></span>
            <div>
                <h2 class="text-sm font-semibold text-amber-950">Tinjau Purchase Request</h2>
                <p class="mt-1 text-xs leading-relaxed text-amber-900">Periksa kebutuhan dan jumlah barang. PO baru bisa dibuat Purchasing setelah PR disetujui.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('approvals.purchase-requests.approve', $purchaseRequest) }}" class="mt-4 space-y-3">
            @csrf
            <label class="block text-xs font-medium text-slate-700">Catatan persetujuan <span class="font-normal text-slate-500">(opsional)</span>
                <textarea name="review_note" maxlength="5000" rows="2" class="mt-1.5 w-full rounded-xl border border-amber-200 bg-white p-3 text-sm text-slate-800 outline-none focus:border-emerald-500" placeholder="Contoh: sesuai kebutuhan operasional"></textarea>
            </label>
            <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 sm:w-auto">
                <span class="material-symbols-outlined text-[18px]">task_alt</span>Setujui PR
            </button>
        </form>
        <form method="POST" action="{{ route('approvals.purchase-requests.reject', $purchaseRequest) }}" class="mt-3 border-t border-amber-200 pt-3">
            @csrf
            <label class="block text-xs font-medium text-slate-700">Alasan penolakan <span class="text-rose-700">(wajib)</span>
                <textarea required minlength="5" name="review_note" maxlength="5000" rows="2" class="mt-1.5 w-full rounded-xl border border-amber-200 bg-white p-3 text-sm text-slate-800 outline-none focus:border-rose-500" placeholder="Jelaskan bagian yang perlu diperbaiki"></textarea>
            </label>
            <button type="submit" class="mt-2 inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-rose-300 bg-white px-4 py-2.5 text-sm font-semibold text-rose-800 transition hover:bg-rose-50">
                <span class="material-symbols-outlined text-[18px]">block</span>Tolak PR
            </button>
        </form>
    </section>
@endif
