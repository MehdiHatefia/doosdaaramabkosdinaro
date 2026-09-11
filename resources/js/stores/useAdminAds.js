import { defineStore } from 'pinia';
import { adService } from '../services';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('AdminAdsStore');

export const useAdminAds = defineStore('adminAds', {
    state: () => ({ ads: [], loading: false }),
    actions: {
        async fetchAds() {
            logger.info('fetchAds', 'Admin ads fetch started');
            this.loading = true;
            try { const data = await adService.getAll(); this.ads = data.data || data; }
            catch (exception) { logger.error('fetchAds', 'Admin ads fetch failed', logException(exception)); throw exception; }
            finally { this.loading = false; }
            logger.info('fetchAds', 'Admin ads state updated', { count: this.ads.length });
        },
    },
});