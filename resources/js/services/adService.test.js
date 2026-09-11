import { describe, expect, it, vi } from 'vitest';

const { request } = vi.hoisted(() => ({ request: vi.fn() }));
vi.mock('./apiClient', () => ({ default: { request } }));
const { adService } = await import('./adService');

describe('adService', () => {
    it.each([
        ['getAll', 'get', '/advertisements', undefined],
        ['getById', 'get', '/advertisements/7', undefined],
        ['create', 'post', '/advertisements', { title: 'new' }],
        ['update', 'put', '/advertisements/7', { title: 'updated' }],
        ['delete', 'delete', '/advertisements/7', undefined],
        ['toggleStatus', 'patch', '/advertisements/7/status', undefined],
    ])('%s sends the expected request', async (methodName, method, url, payload) => {
        request.mockResolvedValue({ status: 200, data: { ok: true } });

        const result = methodName === 'getAll'
            ? await adService[methodName]({ page: 2 })
            : methodName === 'getById' || methodName === 'delete' || methodName === 'toggleStatus'
                ? await adService[methodName](7)
                : methodName === 'create'
                    ? await adService[methodName](payload)
                    : await adService[methodName](7, payload);

        expect(result).toEqual({ ok: true });
        expect(request).toHaveBeenCalled();
        request.mockClear();
    });
});