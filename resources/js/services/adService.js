import apiClient from './apiClient';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('AdService');

async function request(functionName, method, url, config = {}) {
    logger.info(functionName, 'Advertisement request started', { method, url, ...config.params, payload: config.data });
    try {
        const response = await apiClient.request({ method, url, ...config });
        logger.info(functionName, 'Advertisement response received', { status: response.status, url, data: response.data });
        return response.data;
    } catch (exception) {
        logger.error(functionName, 'Advertisement request failed', logException(exception));
        throw exception;
    }
}

export const adService = {
    getAll(params = {}) {
        return request('getAll', 'get', '/advertisements', { params });
    },
    getById(id) {
        return request('getById', 'get', `/advertisements/${id}`);
    },
    getContact(id) {
        return request('getContact', 'post', `/advertisements/${id}/contact`);
    },
    getMyAds(filters = {}, page = 1, perPage = 10) {
        const normalizedFilters = typeof filters === 'string' ? { status: filters } : filters;
        return request('getMyAds', 'get', '/user/advertisements', { params: { ...normalizedFilters, page, per_page: perPage } });
    },
    create(payload) {
        return request('create', 'post', '/advertisements', { data: payload });
    },
    update(id, payload) {
        return request('update', 'put', `/advertisements/${id}`, { data: payload });
    },
    delete(id) {
        return request('delete', 'delete', `/advertisements/${id}`);
    },
        toggleStatus(id, status = 'handed_over') {
            return request('toggleStatus', 'patch', `/advertisements/${id}/status`, { data: { status } });
    },
    getBookmarks() { return request('getBookmarks', 'get', '/user/bookmarks'); },
    toggleBookmark(id) { return request('toggleBookmark', 'post', `/user/bookmarks/${id}`); },
    removeBookmark(id) { return request('removeBookmark', 'delete', `/user/bookmarks/${id}`); },
};

export default adService;