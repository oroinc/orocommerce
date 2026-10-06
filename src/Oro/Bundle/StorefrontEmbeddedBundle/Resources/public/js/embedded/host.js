import {getActiveFragment} from './active-fragment';
import {FILE_PATH_PREFIXES} from './config';

let proxyPrefix = '';
let oroBaseUrl = '';
let isAnotherPage = () => false;
let updateQueryParams = () => {};

export function configureRuntime({
    proxyPrefix: prefix,
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
    if (typeof prefix === 'string') {
        proxyPrefix = prefix;
    }
    if (baseUrl) {
        oroBaseUrl = baseUrl.replace(/\/$/, '');
    }
}

export function getProxyPrefix() {
    return proxyPrefix;
}

export function getOroBaseUrl() {
    return oroBaseUrl;
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
    return isOroPath(url) && FILE_PATH_PREFIXES.some(prefix => url.startsWith(prefix));
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
