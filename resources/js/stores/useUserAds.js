import { defineStore } from 'pinia';
import { adService } from '../services';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('UserAdsStore');

export const useUserAds = defineStore('userAds', {
    state: () => ({ ads: [], loading: false }),
    actions: {
        async fetchAds(status = '') {
            logger.info('fetchAds', 'User ads fetch started');
            this.loading = true;
            try { const data = await adService.getMyAds(status); this.ads = data.data || data; }
            catch (exception) { logger.error('fetchAds', 'User ads fetch failed', logException(exception)); throw exception; }
            finally { this.loading = false; }
            logger.info('fetchAds', 'User ads state updated', { count: this.ads.length });
        },
    },
});