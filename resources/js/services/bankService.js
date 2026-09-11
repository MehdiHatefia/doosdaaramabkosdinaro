import apiClient from './apiClient';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('BankService');

async function request(functionName, url) {
    logger.info(functionName, 'Bank request started', { url });
    try {
        const response = await apiClient.get(url);
        logger.info(functionName, 'Bank response received', { status: response.status, url, data: response.data });
        return response.data;
    } catch (exception) {
        logger.error(functionName, 'Bank request failed', logException(exception));
        throw exception;
    }
}

export const bankService = {
    getAll() {
        return request('getAll', '/banks');
    },
    getById(id) {
        return request('getById', `/banks/${id}`);
    },
    getPlans(id) {
        return request('getPlans', `/banks/${id}/plans`);
    },
};

export default bankService;