import { beforeEach, describe, expect, it, vi } from 'vitest';

const { request } = vi.hoisted(() => ({ request: vi.fn() }));
vi.mock('./apiClient', () => ({ default: { request } }));
const { authService } = await import('./authService');

describe('authService', () => {
    beforeEach(() => {
        localStorage.clear();
        request.mockReset();
    });

    it('sends an OTP request', async () => {
        request.mockResolvedValue({ status: 200, data: { message: 'ok' } });

        await authService.sendOtp('09123456789');
        expect(request).toHaveBeenCalledWith({ method: 'post', url: '/auth/send-otp', data: { mobile: '09123456789' } });
    });

    it('verifies OTP and stores authentication state', async () => {
        request.mockResolvedValue({ status: 200, data: { token: 'token', user: { id: 1 } } });

        await authService.verifyOtp({ mobile: '09123456789', code: '12345' });
        expect(localStorage.getItem('auth_token')).toBe('token');
        expect(JSON.parse(localStorage.getItem('auth_user'))).toEqual({ id: 1 });
    });

    it('logs out and clears authentication state', async () => {
        localStorage.setItem('auth_token', 'token');
        localStorage.setItem('auth_user', '{}');
        request.mockResolvedValue({ status: 204, data: null });

        await authService.logout();
        expect(localStorage.getItem('auth_token')).toBeNull();
        expect(localStorage.getItem('auth_user')).toBeNull();
    });

    it('gets the authenticated profile', async () => {
        request.mockResolvedValue({ status: 200, data: { id: 1 } });

        await expect(authService.getProfile()).resolves.toEqual({ id: 1 });
        expect(request).toHaveBeenCalledWith({ method: 'get', url: '/user/profile', data: undefined });
    });
});