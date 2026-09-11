import apiClient from './apiClient';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('VerificationService');
async function request(functionName, method, url, config = {}) {
    try {
        const response = await apiClient.request({ method, url, ...config });
        logger.info(functionName, 'Verification response received', { status: response.status, url });
        return response.data;
    } catch (exception) {
        logger.error(functionName, 'Verification request failed', logException(exception));
        throw exception;
    }
}
export const verificationService = {
    list(params = {}) { return request('list', 'get', '/admin/verifications', { params }); },
    get(id) { return request('get', 'get', `/admin/verifications/${id}`); },
    review(id, payload) { return request('review', 'patch', `/admin/verifications/${id}/review`, { data: payload }); },
};
export default verificationService;
