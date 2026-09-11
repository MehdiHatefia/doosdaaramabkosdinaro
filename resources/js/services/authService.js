import apiClient from './apiClient';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('AuthService');

async function request(functionName, method, url, data) {
    const safePayload = data ? { ...data, code: data.code ? '[REDACTED]' : data.code } : data;
    logger.info(functionName, 'Authentication request started', { method, url, payload: safePayload });
    try {
        const response = await apiClient.request({ method, url, data });
        logger.info(functionName, 'Authentication response received', {
            status: response.status,
            url,
            hasToken: Boolean(response.data?.token),
            hasUser: Boolean(response.data?.user),
        });
        return response.data;
    } catch (exception) {
        logger.error(functionName, 'Authentication request failed', logException(exception));
        throw exception;
    }
}

export const authService = {
    sendOtp(mobile) {
        return request('sendOtp', 'post', '/auth/send-otp', { mobile });
    },
    async verifyOtp({ mobile, code }) {
        const data = await request('verifyOtp', 'post', '/auth/verify-otp', { mobile, code });
        if (data.token) localStorage.setItem('auth_token', data.token);
        if (data.user) localStorage.setItem('auth_user', JSON.stringify(data.user));
        return data;
    },
    async completeRegistration({ mobile, code, name, email }) {
        const data = await request('completeRegistration', 'post', '/auth/complete-registration', { mobile, code, name, email });
        if (data.token) localStorage.setItem('auth_token', data.token);
        if (data.user) localStorage.setItem('auth_user', JSON.stringify(data.user));
        return data;
    },
    async logout() {
        const data = await request('logout', 'post', '/auth/logout');
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
        return data;
    },
    getProfile() {
        return request('getProfile', 'get', '/user/profile');
    },
    async updateProfile(payload) {
        const data = await request('updateProfile', 'put', '/user/profile', payload);
        if (data.user) localStorage.setItem('auth_user', JSON.stringify(data.user));
        return data;
    },
    async uploadAvatar(file) {
        logger.info('uploadAvatar', 'Avatar upload started', { hasFile: Boolean(file) });
        try {
            const formData = new FormData();
            formData.append('avatar', file);
            const response = await apiClient.post('/user/profile/avatar', formData);
            logger.info('uploadAvatar', 'Avatar upload completed', { status: response.status, hasUser: Boolean(response.data?.user) });
            if (response.data?.user) localStorage.setItem('auth_user', JSON.stringify(response.data.user));
            return response.data;
        } catch (exception) {
            logger.error('uploadAvatar', 'Avatar upload failed', logException(exception));
            throw exception;
        }
    },
    async getAvatarPreview() {
        const response = await apiClient.get('/user/profile/avatar', { responseType: 'blob' });
        return URL.createObjectURL(response.data);
    },
    submitKyc(formData) { return request('submitKyc', 'post', '/user/kyc/submit', formData); },
    getKycStatus() { return request('getKycStatus', 'get', '/user/kyc/status'); },
    getKycHistory() { return request('getKycHistory', 'get', '/user/kyc/history'); },
    getKycDetails(id) { return request('getKycDetails', 'get', `/user/kyc/${id}/details`); },
    async getKycDocument(id, type) {
        const response = await apiClient.get(`/user/kyc/${id}/documents/${type}`, { responseType: 'blob' });
        return URL.createObjectURL(response.data);
    },
};

export default authService;