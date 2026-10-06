/* global require */
import moduleConfig from 'module-config';
import {toProxiedOroUrl} from './tools/url';

// The loaders fetch their resources on import, before the request proxy is installed.
const LOADER_RESOURCES = [
    [require.resolveWeak('oronavigation/js/routes-loader'), 'routesResource'],
    [require.resolveWeak('orotranslation/js/translation-loader'), 'translationsResources']
];

/**
 * Points the resources of the routes and translations loaders to the host proxy.
 *
 * @param {function(string): Object} [configOf] reads the configuration of a module by its id
 */
export function proxyLoaderResources(configOf = moduleConfig) {
    for (const [moduleId, option] of LOADER_RESOURCES) {
        const config = configOf(moduleId);
        if (option in config) {
            config[option] = toProxiedOroUrl(config[option]);
        }
    }
}
