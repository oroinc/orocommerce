/* global require */
import {configureRuntime} from 'orostorefrontembedded/js/embedded/host';
import {proxyLoaderResources} from 'orostorefrontembedded/js/embedded/loader-resources';

describe('orostorefrontembedded/js/embedded/loader-resources', () => {
    const ORO = 'http://oro.loc';
    const ROUTES = `${ORO}/media/js/frontend_routes.json?version=1`;
    const TRANSLATIONS = `${ORO}/media/js/translation/en.json?version=1`;
    // The spec only resolves the loaders, so they never run and fetch nothing.
    const ROUTES_LOADER = require.resolve('oronavigation/js/routes-loader');
    const TRANSLATION_LOADER = require.resolve('orotranslation/js/translation-loader');

    let configs;
    let configOf;

    beforeEach(() => {
        configs = new Map([
            [ROUTES_LOADER, {routesResource: ROUTES}],
            [TRANSLATION_LOADER, {translationsResources: TRANSLATIONS}]
        ]);
        configOf = moduleId => configs.get(moduleId) ?? {};
    });

    it('points the routes and translations the loaders fetch at the host proxy', () => {
        configureRuntime({oroBaseUrl: ORO, proxyPrefix: '/__oro'});

        proxyLoaderResources(configOf);

        expect(configs.get(ROUTES_LOADER).routesResource).toBe('/__oro/media/js/frontend_routes.json?version=1');
        expect(configs.get(TRANSLATION_LOADER).translationsResources)
            .toBe('/__oro/media/js/translation/en.json?version=1');
    });

    it('does not prefix the resources twice when the boot runs again', () => {
        configureRuntime({oroBaseUrl: ORO, proxyPrefix: '/__oro'});

        proxyLoaderResources(configOf);
        proxyLoaderResources(configOf);

        expect(configs.get(ROUTES_LOADER).routesResource).toBe('/__oro/media/js/frontend_routes.json?version=1');
    });

    it('keeps the Oro urls when the host names no proxy prefix', () => {
        configureRuntime({oroBaseUrl: ORO, proxyPrefix: ''});

        proxyLoaderResources(configOf);

        expect(configs.get(ROUTES_LOADER).routesResource).toBe(ROUTES);
        expect(configs.get(TRANSLATION_LOADER).translationsResources).toBe(TRANSLATIONS);
    });

    it('keeps resources served from another origin', () => {
        configureRuntime({oroBaseUrl: ORO, proxyPrefix: '/__oro'});
        configs.get(ROUTES_LOADER).routesResource = 'https://cdn.example/media/js/frontend_routes.json';
        configs.get(TRANSLATION_LOADER).translationsResources = 'https://cdn.example/media/js/translation/en.json';

        proxyLoaderResources(configOf);

        expect(configs.get(ROUTES_LOADER).routesResource).toBe('https://cdn.example/media/js/frontend_routes.json');
        expect(configs.get(TRANSLATION_LOADER).translationsResources)
            .toBe('https://cdn.example/media/js/translation/en.json');
    });

    it('adds no resource to a loader config that has none', () => {
        configureRuntime({oroBaseUrl: ORO, proxyPrefix: '/__oro'});
        configs.set(ROUTES_LOADER, {});

        proxyLoaderResources(configOf);

        expect(configs.get(ROUTES_LOADER)).toEqual({});
    });
});
