import {getActiveFragment} from './active-fragment';

let publicPath = '';
let proxyPrefix = '';
let oroBaseUrl = '';
let isAnotherPage = () => false;
let updateQueryParams = () => {};
const proxiedRoutes = new Set();
const filePaths = new Set();

export function configureRuntime({
    publicPath: path,
    proxyPrefix: prefix,
    proxiedRoutes: routes,
    filePaths: oroFilePaths,
    oroBaseUrl: baseUrl,
    isAnotherPage: hostIsAnotherPage,
    updateQueryParams: hostUpdateQueryParams
} = {}) {
    if (hostIsAnotherPage) {
        isAnotherPage = hostIsAnotherPage;
    }
    if (hostUpdateQueryParams) {
        updateQueryParams = hostUpdateQueryParams;
    }
    if (path) {
        publicPath = path;
    }
    if (typeof prefix === 'string') {
        proxyPrefix = prefix;
    }
    if (baseUrl) {
        oroBaseUrl = baseUrl.replace(/\/$/, '');
    }
    for (const route of routes ?? []) {
        proxiedRoutes.add(route);
    }
    for (const filePath of oroFilePaths ?? []) {
        filePaths.add(filePath);
    }
}

export function getPublicPath() {
    return publicPath;
}

export function getProxyPrefix() {
    return proxyPrefix;
}

export function getOroBaseUrl() {
    return oroBaseUrl;
}

export function isProxiedRoute(routeName) {
    return proxiedRoutes.has(routeName);
}

export function isAnotherHostPage(url) {
    return isAnotherPage(url) === true;
}

export function updateHostQueryParams(query) {
    updateQueryParams(query);
}

export function toProxiedPath(url) {
    return proxyPrefix && isOroPath(url) && !url.startsWith(`${proxyPrefix}/`) ? proxyPrefix + url : url;
}

function isOroPath(url) {
    return typeof url === 'string' && url.startsWith('/') && !url.startsWith('//');
}

function isOroFileUrl(url) {
    if (!isOroPath(url)) {
        return false;
    }

    for (const filePath of filePaths) {
        if (url.startsWith(filePath)) {
            return true;
        }
    }

    return false;
}

export function navigateFragmentTo(fragment, url, options = {}) {
    if (fragment?.navigate?.(url, options) === true) {
        return false;
    }

    const opensFile = isOroFileUrl(url);
    const target = opensFile ? toProxiedPath(url) : url;

    if (options.newTab) {
        window.open(target, '_blank');

        return false;
    }

    window.location.href = target;

    return !opensFile;
}

export function navigateTo(url, options) {
    return navigateFragmentTo(getActiveFragment(), url, options);
}

export function combineRouteUrl(path, query) {
    if (typeof path === 'object' && path !== null) {
        query = path.query;
        path = path.path;
    }
    path = (path[0] !== '/' ? '/' : '') + path;

    return path + (query ? `?${query}` : '');
}
