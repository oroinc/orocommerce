import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import {navigateTo, combineRouteUrl, updateHostQueryParams} from './host';
import {isCurrentPath, toHostPath, toInternalPath} from './tools/url';
import {getActiveFragment} from './active-fragment';

const showActiveFragmentLoading = () => getActiveFragment()?.loadingMask.show();
const hideActiveFragmentLoading = () => getActiveFragment()?.loadingMask.hide();
const reloadActiveFragment = () => $.when(getActiveFragment()?.reload());

export function registerEmbeddedHandlers() {
    mediator.setHandler('retrieveOption', () => undefined);
    mediator.setHandler('retrievePath', path => String(path).replace(/^\//, ''));
    mediator.setHandler('combineRouteUrl', combineRouteUrl);
    mediator.setHandler('combineFullUrl', (path, query) => {
        const url = combineRouteUrl(path, query);
        return url[0] === '/' ? url : `/${url}`;
    });

    mediator.setHandler('showLoading', showActiveFragmentLoading);
    mediator.setHandler('hideLoading', hideActiveFragmentLoading);
    mediator.on('page:beforeChange', showActiveFragmentLoading);
    mediator.on('page:afterChange', hideActiveFragmentLoading);

    mediator.setHandler('redirectTo', (pathDesc = {}, redirectOptions = {}) => {
        if (!pathDesc.url) {
            return $.when();
        }

        const newTab = redirectOptions.target === '_blank';
        const oroPath = toInternalPath(pathDesc.url);

        if (oroPath !== null && !newTab && isCurrentPath(toHostPath(oroPath))) {
            return reloadActiveFragment();
        }

        navigateTo(oroPath ?? pathDesc.url, {newTab});

        return $.when();
    });
    mediator.setHandler('refreshPage', reloadActiveFragment);
    mediator.setHandler('changeRoute', route => updateHostQueryParams(route?.query ?? ''));

    mediator.setHandler('submitPage', ajaxOptions => $.ajax(ajaxOptions));
}
