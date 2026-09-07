import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import {navigateTo, combineRouteUrl, updateHostQueryParams} from './host';
import {toInternalPath} from './tools/url';
import {getActiveFragment} from './active-fragment';

const showActiveFragmentLoading = () => getActiveFragment()?.loadingMask.show();
const hideActiveFragmentLoading = () => getActiveFragment()?.loadingMask.hide();
const toPathIfOroUrl = url => toInternalPath(url) ?? url;

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
        if (pathDesc.url) {
            navigateTo(toPathIfOroUrl(pathDesc.url), {newTab: redirectOptions.target === '_blank'});
        }
        return $.when();
    });
    mediator.setHandler('refreshPage', () => $.when(getActiveFragment()?.reload()));
    mediator.setHandler('changeRoute', route => updateHostQueryParams(route?.query ?? ''));

    mediator.setHandler('submitPage', ajaxOptions => $.ajax(ajaxOptions));
}
