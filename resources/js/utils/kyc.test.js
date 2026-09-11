import { describe, expect, it, vi } from 'vitest';
import { previewKycFile, validateKycFile } from './kyc';

describe('KYC file helpers', () => {
    it('rejects unsupported and oversized files', () => {
        expect(validateKycFile(new File(['x'], 'document.txt', { type: 'text/plain' }))).toContain('فرمت');
        expect(validateKycFile(new File([new Uint8Array(4 * 1024 * 1024 + 1)], 'document.jpg', { type: 'image/jpeg' }))).toContain('حجم');
    });

    it('creates an image preview and skips PDF previews', () => {
        const createObjectURL = vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:image');
        const image = new File(['x'], 'document.jpg', { type: 'image/jpeg' });
        const pdf = new File(['x'], 'document.pdf', { type: 'application/pdf' });

        expect(previewKycFile(image)).toBe('blob:image');
        expect(previewKycFile(pdf)).toBeNull();
        expect(createObjectURL).toHaveBeenCalledWith(image);
        createObjectURL.mockRestore();
    });
});
