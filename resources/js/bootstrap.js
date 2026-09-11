import axios from 'axios';
import { createLogger, logException } from './utils/logger';

const logger = createLogger('ApiClient');
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.interceptors.request.use((config) => {
	logger.info('request', 'API request started', { method: config.method, url: config.url });
	return config;
}, (exception) => {
	logger.error('request', 'API request setup failed', logException(exception));
	return Promise.reject(exception);
});
window.axios.interceptors.response.use((response) => {
	logger.info('response', 'API response received', { status: response.status, url: response.config?.url });
	return response;
}, (exception) => {
	logger.error('response', 'API response failed', {
		...logException(exception),
		url: exception.config?.url,
	});
	return Promise.reject(exception);
});
