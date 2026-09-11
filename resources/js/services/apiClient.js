import axios from 'axios';
import { createLogger, logException } from '../utils/logger';
import { showToast } from '../utils/toastBus';

const logger = createLogger('ApiClient');

function validationMessage(data) {
    const messages = Object.values(data?.errors || {}).flat();
    return messages[0] || data?.message || 'اطلاعات وارد شده نامعتبر است';
}

function errorMessage(exception) {
    if (exception.code === 'ERR_NETWORK' || !exception.response) return 'ارتباط با اینترنت برقرار نیست. لطفاً اتصال خود را بررسی کنید';

    const status = exception.response.status;
    const data = exception.response.data;
    if (status === 401) return 'نشست شما منقضی شده است. مجدداً وارد شوید';
    if (status === 403) return data?.message || 'دسترسی شما به این عملیات محدود است';
    if (status === 422) return validationMessage(data);
    if (status === 429) return 'درخواست‌های بیش از حد مجاز. لطفاً کمی صبر کرده و مجدد تلاش کنید';
    if (status >= 500) return 'خطای سرور. لطفاً بعداً دوباره امتحان کنید';
    if (status === 404) return 'مورد درخواستی یافت نشد';
    return 'انجام عملیات با خطا مواجه شد. لطفاً دوباره تلاش کنید';
}

export const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
});

apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    if (config.data instanceof FormData) delete config.headers['Content-Type'];

    logger.info('Request', 'API request started', {
        method: config.method,
        url: config.url,
        params: config.params,
        hasToken: Boolean(token),
    });
    return config;
}, (exception) => {
    logger.error('Error', 'API request setup failed', logException(exception));
    return Promise.reject(exception);
});

apiClient.interceptors.response.use((response) => {
    logger.info('Response', 'API response received', {
        status: response.status,
        url: response.config?.url,
    });
    return response;
}, (exception) => {
    logger.error('Error', 'API response failed', {
        ...logException(exception),
        url: exception.config?.url,
    });
    showToast(errorMessage(exception));
    if (exception.response?.status === 401) {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
        window.dispatchEvent(new CustomEvent('auth:unauthorized'));
    }
    if (exception.response?.status === 403 && ['KYC_REQUIRED', 'USER_UNVERIFIED'].includes(exception.response?.data?.code)) {
        logger.warn('Response', 'Verified-user access required', { code: exception.response.data.code, url: exception.config?.url });
        window.dispatchEvent(new CustomEvent('auth:verification-required', { detail: exception.response.data }));
    }
    return Promise.reject(exception);
});

export default apiClient;