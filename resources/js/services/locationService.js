import apiClient from './apiClient';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('LocationService');

export async function getProvinces() {
    logger.info('getProvinces', 'Location request started');
    try {
        const response = await apiClient.get('/locations/provinces');
        logger.info('getProvinces', 'Location response received', { status: response.status, count: response.data?.data?.length || 0 });
        return response.data;
    } catch (exception) {
        logger.error('getProvinces', 'Location request failed', logException(exception));
        throw exception;
    }
}

export async function getCities(provinceId) {
    logger.info('getCities', 'City request started', { provinceId });
    try {
        const response = await apiClient.get(`/locations/${provinceId}/cities`);
        logger.info('getCities', 'City response received', { status: response.status, count: response.data?.data?.length || 0 });
        return response.data;
    } catch (exception) {
        logger.error('getCities', 'City request failed', logException(exception));
        throw exception;
    }
}

export const locationService = { getProvinces, getCities };
export default locationService;