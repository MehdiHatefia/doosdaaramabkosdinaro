import { defineStore } from 'pinia';
import { adService } from '../services';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('UserAdsStore');

export const useUserAds = defineStore('userAds', {
    state: () => ({ ads: [], meta: { current_page: 1, last_page: 1, total: 0, per_page: 10 }, loading: false }),
    actions: {
        async fetchAds(filters = {}, page = 1, perPage = 10) {
            logger.info('fetchAds', 'User ads fetch started');
            this.loading = true;
            try {
            const response = await adService.getMyAds(filters, page, perPage);
                this.ads = response.data || response;
                this.meta = response.meta || this.meta;
            }
            catch (exception) { logger.error('fetchAds', 'User ads fetch failed', logException(exception)); throw exception; }
            finally { this.loading = false; }
            logger.info('fetchAds', 'User ads state updated', { count: this.ads.length });
        },
    },
});