export const KYC_MAX_FILE_SIZE = 4 * 1024 * 1024;
export const KYC_FILE_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];

export function validateKycFile(file) {
    if (!file) return 'انتخاب فایل الزامی است.';
    if (!KYC_FILE_TYPES.includes(file.type)) return 'فرمت فایل باید JPG، PNG یا PDF باشد.';
    if (file.size > KYC_MAX_FILE_SIZE) return 'حجم فایل نباید بیشتر از ۴ مگابایت باشد.';
    return '';
}

export function previewKycFile(file) {
    return file?.type?.startsWith('image/') ? URL.createObjectURL(file) : null;
}