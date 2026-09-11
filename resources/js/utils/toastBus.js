export function showToast(detail, summary = 'خطا') {
    if (typeof window === 'undefined') return;

    window.dispatchEvent(new CustomEvent('app:toast', {
        detail: { severity: 'error', summary, detail, life: 4500 },
    }));
}