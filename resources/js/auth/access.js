import { createLogger, logException } from '../utils/logger';

export const ADMIN_ROLES = ['super-admin', 'admin', 'operator'];
const logger = createLogger('AuthAccess');

export function getStoredUser() {
    try {
        const user = JSON.parse(localStorage.getItem('auth_user') || 'null');
        logger.info('getStoredUser', 'Stored user loaded', { hasUser: Boolean(user) });
        return user;
    } catch (exception) {
        logger.error('getStoredUser', 'Stored user could not be parsed', logException(exception));
        return null;
    }
}

export function isAuthenticated() {
    const authenticated = Boolean(localStorage.getItem('auth_token'));
    logger.info('isAuthenticated', 'Authentication state checked', { authenticated });
    return authenticated;
}

export function userRoles(user = getStoredUser()) {
    if (!user) {
        logger.info('userRoles', 'No user roles available', { roleCount: 0 });
        return [];
    }
    const roles = user.roles || user.role || [];
    const normalizedRoles = (Array.isArray(roles) ? roles : [roles]).map((role) => {
        const roleName = typeof role === 'object' ? role.name : role;
        const aliases = { 'Super Admin': 'super-admin', Admin: 'admin', Operator: 'operator' };
        return aliases[roleName] || roleName;
    });
    logger.info('userRoles', 'User roles normalized', { roleCount: normalizedRoles.length });
    return normalizedRoles;
}

export function canAccessAdmin(user = getStoredUser()) {
    const allowed = userRoles(user).some((role) => ADMIN_ROLES.includes(role));
    logger.info('canAccessAdmin', 'Admin access checked', { allowed });
    return allowed;
}