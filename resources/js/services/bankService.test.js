import { describe, expect, it, vi } from 'vitest';

const { get } = vi.hoisted(() => ({ get: vi.fn() }));
vi.mock('./apiClient', () => ({ default: { get } }));
const { bankService } = await import('./bankService');

describe('bankService', () => {
    it('gets all active banks', async () => {
        get.mockResolvedValue({ status: 200, data: { data: [{ id: 1 }] } });

        await expect(bankService.getAll()).resolves.toEqual({ data: [{ id: 1 }] });
        expect(get).toHaveBeenCalledWith('/banks');
    });

    it('gets one bank by id', async () => {
        get.mockResolvedValue({ status: 200, data: { data: { id: 4 } } });

        await expect(bankService.getById(4)).resolves.toEqual({ data: { id: 4 } });
        expect(get).toHaveBeenCalledWith('/banks/4');
    });
});