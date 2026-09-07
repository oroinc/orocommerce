import {getOroBaseUrl, getProxyPrefix} from '../host';

const LAYOUT_BLOCK_IDS_PARAM = 'layout_block_ids';

export function toInternalPath(url) {
    if (isInternalPath(url)) {
        return url;
    }

    const oroBaseUrl = toUrl(getOroBaseUrl());
    const target = toUrl(url);

    if (!oroBaseUrl || !target || target.origin !== oroBaseUrl.origin) {
        return null;
    }

    return withoutOroBasePath(target.href.slice(target.origin.length), oroBaseUrl.pathname);
}

function isInternalPath(url) {
    return url.startsWith('/') && !url.startsWith('//');
}

function withoutOroBasePath(path, oroBasePath) {
    return oroBasePath !== '/' && path.startsWith(oroBasePath) ? path.slice(oroBasePath.length) : path;
}

function toUrl(url) {
    try {
        return url ? new URL(url) : null;
    } catch {
        return null;
    }
}

export function withQuery(path, query) {
    return path.split('?')[0] + (query ? `?${query}` : '');
}

export function isCurrentPath(path) {
    return withoutTrailingSlash(path) === withoutTrailingSlash(window.location.pathname + window.location.search);
}

export function toHostPath(location) {
    const landed = new URL(location, window.location.origin);
    for (const key of [...landed.searchParams.keys()]) {
        if (key.startsWith(LAYOUT_BLOCK_IDS_PARAM)) {
            landed.searchParams.delete(key);
        }
    }

    const prefix = getProxyPrefix();
    const path = prefix && landed.pathname.startsWith(prefix) ? landed.pathname.slice(prefix.length) : landed.pathname;

    return path + landed.search;
}

function withoutTrailingSlash(path) {
    return path.replace(/\/(\?|$)/, '$1');
}
