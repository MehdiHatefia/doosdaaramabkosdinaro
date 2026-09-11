import { beforeEach, describe, expect, it, vi } from 'vitest';

const { create, showToast } = vi.hoisted(() => ({ create: vi.fn(), showToast: vi.fn() }));
vi.mock('axios', () => ({ default: { create } }));
vi.mock('../utils/toastBus', () => ({ showToast }));

describe('apiClient', () => {
    let responseErrorHandler;

    beforeEach(() => {
        localStorage.clear();
        create.mockReset();
        showToast.mockReset();
        create.mockReturnValue({
            interceptors: {
                request: { use: vi.fn() },
                response: { use: vi.fn((success, error) => { responseErrorHandler = error; }) },
            },
        });
    });

    it('creates an Axios client with the API base URL', async () => {
        await import('./apiClient');

        expect(create).toHaveBeenCalledWith(expect.objectContaining({ baseURL: '/api' }));
    });

    it.each([
        [422, { errors: { email: ['فرمت ایمیل معتبر نیست.'] } }, 'فرمت ایمیل معتبر نیست.'],
        [500, { message: 'خاموشی داخلی سرور' }, 'خطای سرور. لطفاً بعداً دوباره امتحان کنید'],
    ])('shows a Persian toast for HTTP %s errors', async (status, data, message) => {
        await import('./apiClient');

        await expect(responseErrorHandler({ response: { status, data }, config: { url: '/test' } })).rejects.toBeDefined();
        expect(showToast).toHaveBeenCalledWith(message);
    });
});