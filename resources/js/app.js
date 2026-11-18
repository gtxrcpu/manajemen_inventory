import './bootstrap';
import { createIcons, Mail, Lock, Eye, EyeOff, ArrowRight } from 'lucide';

createIcons({
    icons: {
        Mail,
        Lock,
        Eye,
        EyeOff,
        ArrowRight
    }
});

let xlsxModulePromise;
let pdfModulesPromise;
window.prepareStockExport = () => {
    xlsxModulePromise ??= import('xlsx-js-style');
    pdfModulesPromise ??= Promise.all([import('jspdf'), import('jspdf-autotable')]);
    Promise.all([xlsxModulePromise, pdfModulesPromise]).catch(error => console.warn('Export library could not be loaded yet.', error));
};

window.generateStockExport = async (format, rows) => {
    const date = new Date().toISOString().slice(0, 10);
    if (format === 'xlsx') {
        window.prepareStockExport();
        const XLSX = await xlsxModulePromise;
        const reportRows = [
            ['LAPORAN DATA STOK'],
            [`Diekspor: ${new Date().toLocaleString('id-ID')}`],
            [`Jumlah produk: ${Math.max(0, rows.length - 1)}`],
            [],
            rows[0],
            ...rows.slice(1),
        ];
        const sheet = XLSX.utils.aoa_to_sheet(reportRows);
        const lastRow = reportRows.length;
        const border = { style: 'thin', color: { rgb: 'D9E2F3' } };
        sheet['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 4 } }];
        sheet['!cols'] = [{ wch: 18 }, { wch: 34 }, { wch: 24 }, { wch: 20 }, { wch: 16 }];
        sheet['!rows'] = [{ hpt: 30 }, { hpt: 20 }, { hpt: 20 }, { hpt: 10 }, { hpt: 24 }];
        sheet['!freeze'] = { xSplit: 0, ySplit: 5, topLeftCell: 'A6', activePane: 'bottomLeft', state: 'frozen' };
        if (lastRow >= 5) sheet['!autofilter'] = { ref: `A5:E${lastRow}` };
        for (let col = 0; col < 5; col++) {
            const titleCell = XLSX.utils.encode_cell({ r: 0, c: col });
            if (col > 0) sheet[titleCell] = { t: 's', v: '', s: { fill: { fgColor: { rgb: '17365D' } } } };
            sheet[titleCell].s = { fill: { fgColor: { rgb: '17365D' } }, font: { name: 'Aptos Display', sz: 16, bold: true, color: { rgb: 'FFFFFF' } }, alignment: { vertical: 'center' } };
            const headerCell = sheet[XLSX.utils.encode_cell({ r: 4, c: col })];
            if (headerCell) headerCell.s = { fill: { fgColor: { rgb: 'C6283D' } }, font: { name: 'Aptos', bold: true, color: { rgb: 'FFFFFF' } }, alignment: { vertical: 'center', horizontal: col === 3 ? 'right' : 'left' }, border: { top: border, bottom: border } };
            for (let row = 5; row < lastRow; row++) {
                const ref = XLSX.utils.encode_cell({ r: row, c: col });
                if (!sheet[ref]) continue;
                sheet[ref].s = { fill: { fgColor: { rgb: row % 2 === 0 ? 'F3F6FA' : 'FFFFFF' } }, font: { name: 'Aptos', sz: 10, color: { rgb: '243247' } }, alignment: { vertical: 'center', horizontal: col === 3 ? 'right' : 'left', wrapText: true }, border: { bottom: border } };
            }
        }
        const workbook = XLSX.utils.book_new();
        workbook.Props = { Title: 'Laporan Data Stok', Subject: 'Data stok inventory', Author: 'Inventory' };
        XLSX.utils.book_append_sheet(workbook, sheet, 'Data Stok');
        XLSX.writeFile(workbook, `data-stok-${date}.xlsx`);
        return;
    }

    if (format === 'pdf') {
        window.prepareStockExport();
        const [{ jsPDF }, { default: autoTable }] = await pdfModulesPromise;
        const document = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        document.setFontSize(16);
        document.text('Data Stok', 14, 16);
        document.setFontSize(9);
        document.setTextColor(100);
        document.text(`Diekspor ${new Date().toLocaleString('id-ID')}`, 14, 22);
        autoTable(document, {
            head: [rows[0]],
            body: rows.slice(1),
            startY: 28,
            styles: { fontSize: 9, cellPadding: 2.5 },
            headStyles: { fillColor: [220, 20, 60] },
            alternateRowStyles: { fillColor: [249, 249, 249] },
            margin: { left: 14, right: 14 },
        });
        document.save(`data-stok-${date}.pdf`);
    }
};

window.generateProductImportTemplate = async () => {
    const XLSX = await import('xlsx-js-style');
    const headers = ['SKU', 'Nama Produk', 'Kategori', 'Satuan', 'Deskripsi', 'Lacak Kedaluwarsa', 'Aktif'];
    const productsSheet = XLSX.utils.aoa_to_sheet([headers]);
    productsSheet['!cols'] = [{ wch: 20 }, { wch: 30 }, { wch: 24 }, { wch: 18 }, { wch: 38 }, { wch: 24 }, { wch: 14 }];
    productsSheet['!freeze'] = { xSplit: 0, ySplit: 1, topLeftCell: 'A2', activePane: 'bottomLeft', state: 'frozen' };
    productsSheet['!autofilter'] = { ref: 'A1:G1' };
    headers.forEach((_, column) => {
        const cell = productsSheet[XLSX.utils.encode_cell({ r: 0, c: column })];
        cell.s = { fill: { fgColor: { rgb: 'C6283D' } }, font: { name: 'Aptos', bold: true, color: { rgb: 'FFFFFF' } }, alignment: { vertical: 'center' } };
    });
    productsSheet['!rows'] = [{ hpt: 26 }];

    const guideRows = [
        ['PANDUAN IMPOR PRODUK'],
        ['Isi data produk pada sheet Produk, satu produk untuk setiap baris.'],
        [],
        ['Kolom', 'Cara mengisi'],
        ['SKU', 'Wajib dan unik. Maksimal 64 karakter.'],
        ['Nama Produk', 'Wajib. Maksimal 255 karakter.'],
        ['Kategori', 'Wajib. Gunakan nama kategori aktif yang sudah terdaftar di sistem.'],
        ['Satuan', 'Wajib. Gunakan nama atau simbol satuan aktif yang sudah terdaftar.'],
        ['Deskripsi', 'Opsional. Maksimal 5.000 karakter.'],
        ['Lacak Kedaluwarsa', 'Isi Ya atau Tidak. Kosong berarti Tidak.'],
        ['Aktif', 'Isi Ya atau Tidak. Kosong berarti Ya.'],
        [],
        ['Batas impor', 'Maksimal 1.000 baris produk untuk setiap file.'],
        ['Catatan', 'SKU yang sudah ada tidak akan ditimpa; baris tersebut dilewati dan dilaporkan.'],
    ];
    const guideSheet = XLSX.utils.aoa_to_sheet(guideRows);
    guideSheet['!cols'] = [{ wch: 28 }, { wch: 88 }];
    guideRows.forEach((row, rowIndex) => row.forEach((_, column) => {
        const cell = guideSheet[XLSX.utils.encode_cell({ r: rowIndex, c: column })];
        if (!cell) return;
        if (rowIndex === 0) cell.s = { fill: { fgColor: { rgb: '17365D' } }, font: { name: 'Aptos Display', sz: 16, bold: true, color: { rgb: 'FFFFFF' } } };
        else if (rowIndex === 3) cell.s = { fill: { fgColor: { rgb: 'E8EEF6' } }, font: { name: 'Aptos', bold: true, color: { rgb: '243247' } } };
        else cell.s = { font: { name: 'Aptos', sz: 10, color: { rgb: '243247' } }, alignment: { vertical: 'center', wrapText: true } };
    }));
    const workbook = XLSX.utils.book_new();
    workbook.Props = { Title: 'Template Impor Produk', Subject: 'Template untuk impor produk inventory', Author: 'Inventory' };
    XLSX.utils.book_append_sheet(workbook, productsSheet, 'Produk');
    XLSX.utils.book_append_sheet(workbook, guideSheet, 'Panduan');
    XLSX.writeFile(workbook, 'template-impor-produk.xlsx');
};

window.generateCategoryImportTemplate = async () => {
    const XLSX = await import('xlsx-js-style');
    const headers = ['Nama Kategori', 'Deskripsi', 'Aktif'];
    const categorySheet = XLSX.utils.aoa_to_sheet([headers]);
    categorySheet['!cols'] = [{ wch: 32 }, { wch: 60 }, { wch: 16 }];
    categorySheet['!freeze'] = { xSplit: 0, ySplit: 1, topLeftCell: 'A2', activePane: 'bottomLeft', state: 'frozen' };
    categorySheet['!autofilter'] = { ref: 'A1:C1' };
    headers.forEach((_, column) => {
        categorySheet[XLSX.utils.encode_cell({ r: 0, c: column })].s = { fill: { fgColor: { rgb: 'C6283D' } }, font: { name: 'Aptos', bold: true, color: { rgb: 'FFFFFF' } }, alignment: { vertical: 'center' } };
    });
    categorySheet['!rows'] = [{ hpt: 26 }];

    const guideRows = [
        ['PANDUAN IMPOR KATEGORI'],
        ['Isi satu kategori untuk setiap baris pada sheet Kategori.'],
        [],
        ['Kolom', 'Cara mengisi'],
        ['Nama Kategori', 'Wajib dan unik. Maksimal 255 karakter.'],
        ['Deskripsi', 'Opsional. Maksimal 5.000 karakter.'],
        ['Aktif', 'Isi Ya atau Tidak. Kosong berarti Ya.'],
        [],
        ['Batas impor', 'Maksimal 1.000 baris kategori untuk setiap file.'],
        ['Catatan', 'Kategori yang sudah terdaftar tidak akan ditimpa; baris tersebut dilewati dan dilaporkan.'],
    ];
    const guideSheet = XLSX.utils.aoa_to_sheet(guideRows);
    guideSheet['!cols'] = [{ wch: 28 }, { wch: 88 }];
    guideRows.forEach((row, rowIndex) => row.forEach((_, column) => {
        const cell = guideSheet[XLSX.utils.encode_cell({ r: rowIndex, c: column })];
        if (!cell) return;
        if (rowIndex === 0) cell.s = { fill: { fgColor: { rgb: '17365D' } }, font: { name: 'Aptos Display', sz: 16, bold: true, color: { rgb: 'FFFFFF' } } };
        else if (rowIndex === 3) cell.s = { fill: { fgColor: { rgb: 'E8EEF6' } }, font: { name: 'Aptos', bold: true, color: { rgb: '243247' } } };
        else cell.s = { font: { name: 'Aptos', sz: 10, color: { rgb: '243247' } }, alignment: { vertical: 'center', wrapText: true } };
    }));
    const workbook = XLSX.utils.book_new();
    workbook.Props = { Title: 'Template Impor Kategori', Subject: 'Template untuk impor kategori inventory', Author: 'Inventory' };
    XLSX.utils.book_append_sheet(workbook, categorySheet, 'Kategori');
    XLSX.utils.book_append_sheet(workbook, guideSheet, 'Panduan');
    XLSX.writeFile(workbook, 'template-impor-kategori.xlsx');
};

document.addEventListener('click', async event => {
    const button = event.target.closest('[data-product-template], [data-category-template]');
    if (!button) return;
    const original = button.innerHTML;
    button.disabled = true;
    try {
        if (button.hasAttribute('data-category-template')) await window.generateCategoryImportTemplate();
        else await window.generateProductImportTemplate();
    } catch (error) {
        window.alert(error.message || 'Template Excel tidak dapat dibuat.');
    } finally {
        button.disabled = false;
        button.innerHTML = original;
    }
});

document.addEventListener('submit', async event => {
    const form = event.target.closest('[data-spreadsheet-import-form]');
    if (!form) return;
    event.preventDefault();
    const file = form.querySelector('[data-spreadsheet-import-file]')?.files?.[0];
    const button = form.querySelector('[data-spreadsheet-import-submit]');
    const original = button?.innerHTML;
    if (!file) {
        window.alert('Pilih file Excel yang akan diimpor.');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        window.alert('Ukuran file maksimal 5 MB.');
        return;
    }
    if (button) {
        button.disabled = true;
        button.textContent = 'Membaca file...';
    }
    try {
        const XLSX = await import('xlsx-js-style');
        const workbook = XLSX.read(await file.arrayBuffer(), { type: 'array' });
        const sheet = workbook.Sheets[form.dataset.importSheet] || workbook.Sheets[workbook.SheetNames[0]];
        const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '', raw: false, blankrows: false });
        if (rows.length < 2) throw new Error('Sheet Produk belum berisi baris produk.');
        if (rows.length > 1001) throw new Error('Maksimal 1.000 baris produk per file.');
        form.querySelector('[name="rows"]').value = JSON.stringify(rows);
        HTMLFormElement.prototype.submit.call(form);
    } catch (error) {
        window.alert(error.message || 'File Excel tidak dapat dibaca.');
        if (button) {
            button.disabled = false;
            button.innerHTML = original;
        }
    }
});

window.generateReportExport = async (format, url) => {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error('Data laporan tidak dapat dimuat. Coba terapkan filter kembali.');
    const { title, filename, headers, rows } = await response.json();
    const date = new Date().toLocaleString('id-ID');

    if (format === 'xlsx') {
        const XLSX = await import('xlsx-js-style');
        const sheet = XLSX.utils.aoa_to_sheet([[title], [`Diekspor: ${date}`], [`Jumlah baris: ${rows.length}`], [], headers, ...rows]);
        const columnCount = headers.length;
        const lastRow = rows.length + 5;
        sheet['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: Math.max(0, columnCount - 1) } }];
        sheet['!cols'] = headers.map((header, column) => ({ wch: Math.min(34, Math.max(14, String(header).length + 4, ...rows.slice(0, 100).map(row => String(row[column] ?? '').length + 2))) }));
        sheet['!freeze'] = { xSplit: 0, ySplit: 5, topLeftCell: 'A6', activePane: 'bottomLeft', state: 'frozen' };
        if (rows.length) sheet['!autofilter'] = { ref: `A5:${XLSX.utils.encode_col(columnCount - 1)}${lastRow}` };
        const border = { style: 'thin', color: { rgb: 'E5E7EB' } };
        for (let column = 0; column < columnCount; column++) {
            const titleRef = XLSX.utils.encode_cell({ r: 0, c: column });
            if (column > 0) sheet[titleRef] = { t: 's', v: '' };
            sheet[titleRef].s = { fill: { fgColor: { rgb: '7F1029' } }, font: { name: 'Aptos Display', sz: 16, bold: true, color: { rgb: 'FFFFFF' } }, alignment: { vertical: 'center' } };
            const headerRef = XLSX.utils.encode_cell({ r: 4, c: column });
            if (sheet[headerRef]) sheet[headerRef].s = { fill: { fgColor: { rgb: 'B1002C' } }, font: { name: 'Aptos', bold: true, color: { rgb: 'FFFFFF' } }, border: { top: border, bottom: border }, alignment: { vertical: 'center', wrapText: true } };
            for (let row = 5; row < lastRow; row++) {
                const ref = XLSX.utils.encode_cell({ r: row, c: column });
                if (sheet[ref]) sheet[ref].s = { font: { name: 'Aptos', sz: 10, color: { rgb: '243247' } }, fill: { fgColor: { rgb: row % 2 ? 'FFFFFF' : 'F8FAFC' } }, border: { bottom: border }, alignment: { vertical: 'center', wrapText: true } };
            }
        }
        const workbook = XLSX.utils.book_new();
        workbook.Props = { Title: title, Subject: 'Laporan inventory', Author: 'Inventory' };
        XLSX.utils.book_append_sheet(workbook, sheet, 'Laporan');
        XLSX.writeFile(workbook, `${filename}.xlsx`);
        return;
    }

    const [{ jsPDF }, { default: autoTable }] = await Promise.all([import('jspdf'), import('jspdf-autotable')]);
    const document = new jsPDF({ orientation: headers.length > 6 ? 'landscape' : 'portrait', unit: 'mm', format: 'a4' });
    document.setFontSize(16);
    document.setTextColor(30, 41, 59);
    document.text(title, 14, 16);
    document.setFontSize(9);
    document.setTextColor(100);
    document.text(`Diekspor ${date} · ${rows.length} baris`, 14, 22);
    autoTable(document, {
        head: [headers], body: rows, startY: 28,
        styles: { fontSize: headers.length > 7 ? 7 : 8, cellPadding: 2, overflow: 'linebreak' },
        headStyles: { fillColor: [177, 0, 44] },
        alternateRowStyles: { fillColor: [249, 250, 251] },
        margin: { left: 14, right: 14 },
    });
    document.save(`${filename}.pdf`);
};

document.addEventListener('click', async event => {
    const button = event.target.closest('[data-report-export]');
    if (!button) return;
    const original = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="material-symbols-outlined animate-spin text-[17px]">progress_activity</span>Menyiapkan';
    try {
        await window.generateReportExport(button.dataset.reportExport, button.dataset.exportUrl);
    } catch (error) {
        window.alert(error.message || 'Ekspor laporan gagal.');
    } finally {
        button.disabled = false;
        button.innerHTML = original;
    }
});

const confirmationDialog = document.createElement('dialog');
confirmationDialog.className = 'fixed inset-0 m-auto w-[min(92vw,28rem)] rounded-3xl border border-slate-100 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm overflow-hidden';
confirmationDialog.setAttribute('aria-labelledby', 'friendly-confirm-title');
confirmationDialog.innerHTML = `
    <div class="relative p-6 sm:p-7">
        <button type="button" data-confirm-close aria-label="Tutup konfirmasi" class="absolute right-4 top-4 inline-flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600 active:scale-95 transition-colors">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>
        <div class="flex items-start gap-4">
            <div data-confirm-icon-wrapper class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-[#DC143C] ring-4 ring-rose-50/70 shadow-xs transition-colors">
                <span data-confirm-icon class="material-symbols-outlined text-[24px]">help</span>
            </div>
            <div class="min-w-0 flex-1 pt-0.5">
                <h2 id="friendly-confirm-title" class="font-poppins text-lg font-bold text-slate-900 tracking-tight">Konfirmasi tindakan</h2>
                <p data-confirm-copy class="mt-2 text-sm leading-relaxed text-slate-600"></p>
            </div>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
            <button type="button" data-confirm-cancel class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs hover:bg-slate-50 hover:text-slate-900 active:scale-[0.98] transition">Nanti dulu</button>
            <button type="button" data-confirm-accept class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#DC143C] to-[#B1002C] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-red-900/15 hover:from-[#c81036] hover:to-[#9e0026] active:scale-[0.98] transition">Ya, lanjutkan</button>
        </div>
    </div>`;
document.body.appendChild(confirmationDialog);

const askForConfirmation = ({ title, message, button, tone }) => new Promise(resolve => {
    confirmationDialog.querySelector('#friendly-confirm-title').textContent = title || 'Konfirmasi tindakan';
    confirmationDialog.querySelector('[data-confirm-copy]').textContent = message || 'Yakin ingin melanjutkan tindakan ini?';
    
    const iconWrapper = confirmationDialog.querySelector('[data-confirm-icon-wrapper]');
    const icon = confirmationDialog.querySelector('[data-confirm-icon]');
    const accept = confirmationDialog.querySelector('[data-confirm-accept]');
    
    accept.textContent = button || 'Ya, lanjutkan';

    if (tone === 'danger') {
        iconWrapper.className = 'flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-50 text-red-600 ring-4 ring-red-50/70 shadow-xs transition-colors';
        icon.textContent = 'warning';
        accept.className = 'inline-flex items-center justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-red-600/20 hover:bg-red-700 active:scale-[0.98] transition';
    } else {
        iconWrapper.className = 'flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-[#DC143C] ring-4 ring-rose-50/70 shadow-xs transition-colors';
        icon.textContent = 'help';
        accept.className = 'inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#DC143C] to-[#B1002C] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-red-900/15 hover:from-[#c81036] hover:to-[#9e0026] active:scale-[0.98] transition';
    }

    const finish = () => {
        confirmationDialog.removeEventListener('close', finish);
        resolve(confirmationDialog.returnValue === 'confirm');
    };
    confirmationDialog.addEventListener('close', finish, { once: true });
    
    if (confirmationDialog.open) {
        confirmationDialog.close('cancel');
    }
    confirmationDialog.showModal();
});

confirmationDialog.querySelector('[data-confirm-close]')?.addEventListener('click', () => confirmationDialog.close('cancel'));
confirmationDialog.querySelector('[data-confirm-cancel]').addEventListener('click', () => confirmationDialog.close('cancel'));
confirmationDialog.querySelector('[data-confirm-accept]').addEventListener('click', () => confirmationDialog.close('confirm'));

confirmationDialog.addEventListener('click', event => {
    const rect = confirmationDialog.getBoundingClientRect();
    const isInDialog = (
        rect.top <= event.clientY &&
        event.clientY <= rect.top + rect.height &&
        rect.left <= event.clientX &&
        event.clientX <= rect.left + rect.width
    );
    if (!isInDialog) {
        confirmationDialog.close('cancel');
    }
});

document.addEventListener('submit', async event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    if (form.dataset.confirmAccepted === 'true') {
        delete form.dataset.confirmAccepted;
        return;
    }

    const submitter = event.submitter;
    const source = submitter?.dataset.confirmMessage ? submitter : form;
    if (!source.dataset.confirmMessage) return;

    event.preventDefault();
    const accepted = await askForConfirmation({
        title: source.dataset.confirmTitle,
        message: source.dataset.confirmMessage,
        button: source.dataset.confirmButton,
        tone: source.dataset.confirmTone,
    });
    if (!accepted) return;

    form.dataset.confirmAccepted = 'true';
    if (typeof form.requestSubmit === 'function') form.requestSubmit(submitter || undefined);
    else form.submit();
});

const autoFilterTimers = new WeakMap();

function submitAutoFilter(form) {
    if (form.dataset.autoFilterSubmitting === 'true') return;
    form.dataset.autoFilterSubmitting = 'true';
    form.requestSubmit();
}

document.addEventListener('change', event => {
    const control = event.target;
    if (!(control instanceof HTMLSelectElement || (control instanceof HTMLInputElement && control.type === 'date'))) return;

    const form = control.closest('form[data-auto-filter]');
    if (!form) return;

    const timer = autoFilterTimers.get(form);
    if (timer) window.clearTimeout(timer);
    submitAutoFilter(form);
});

document.addEventListener('input', event => {
    const control = event.target;
    if (!(control instanceof HTMLInputElement) || !['q', 'reference'].includes(control.name)) return;

    const form = control.closest('form[data-auto-filter]');
    if (!form) return;

    const timer = autoFilterTimers.get(form);
    if (timer) window.clearTimeout(timer);
    autoFilterTimers.set(form, window.setTimeout(() => submitAutoFilter(form), 500));
});

document.addEventListener('keydown', event => {
    const control = event.target;
    if (event.key !== 'Enter' || event.isComposing || !(control instanceof HTMLInputElement) || !['q', 'reference'].includes(control.name)) return;

    const form = control.closest('form[data-auto-filter]');
    if (!form) return;

    event.preventDefault();
    const timer = autoFilterTimers.get(form);
    if (timer) window.clearTimeout(timer);
    submitAutoFilter(form);
});
