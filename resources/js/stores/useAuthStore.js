import { defineStore } from 'pinia';
import { createLogger } from '../utils/logger';
import { authService } from '../services/authService';

const logger = createLogger('AuthStore');

function readUser() {
    try {
        return JSON.parse(localStorage.getItem('auth_user') || 'null');
    } catch (exception) {
        logger.error('readUser', 'Stored user could not be parsed', { message: exception?.message });
        return null;
    }
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('auth_token'),
        user: readUser(),
    }),
    getters: {
        isAuthenticated: (state) => Boolean(state.token),
        isVerified: (state) => Boolean(state.user?.is_verified === true || state.user?.verified === true),
    },
    actions: {
        syncFromStorage() {
            this.token = localStorage.getItem('auth_token');
            this.user = readUser();
            logger.info('syncFromStorage', 'Authentication state synchronized', {
                authenticated: Boolean(this.token),
                verified: Boolean(this.user?.is_verified === true || this.user?.verified === true),
            });
        },
        setSession(token, user) {
            if (token) localStorage.setItem('auth_token', token);
            if (user) localStorage.setItem('auth_user', JSON.stringify(user));
            this.token = token || null;
            this.user = user || null;
            logger.info('setSession', 'Authentication session stored', { userId: user?.id, isNewUser: user?.is_new_user });
        },
        async refreshUser() {
            try {
                const response = await authService.getProfile();
                this.user = response.user || response;
                localStorage.setItem('auth_user', JSON.stringify(this.user));
                logger.info('refreshUser', 'Authentication profile refreshed', { userId: this.user?.id, verified: this.user?.is_verified === true });
                return this.user;
            } catch (exception) {
                logger.warn('refreshUser', 'Authentication profile refresh failed', { message: exception?.message });
                return this.user;
            }
        },
        clear() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
            this.token = null;
            this.user = null;
            logger.info('clear', 'Authentication state cleared');
        },
    },
});
