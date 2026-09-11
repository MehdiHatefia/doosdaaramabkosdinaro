const isProduction = import.meta.env.PROD;

function write(level, moduleName, functionName, message, context = {}) {
    if (isProduction && level === 'info') return;

    const prefix = `[${moduleName}:${functionName}]`;
    const method = console[level] || console.log;
    method.call(console, prefix, message, context);
}

export function createLogger(moduleName) {
    return {
        info(functionName, message, context = {}) {
            write('info', moduleName, functionName, message, context);
        },
        warn(functionName, message, context = {}) {
            write('warn', moduleName, functionName, message, context);
        },
        error(functionName, message, context = {}) {
            write('error', moduleName, functionName, message, context);
        },
    };
}

export function logException(exception) {
    return {
        message: exception?.message,
        status: exception?.response?.status,
    };
}
