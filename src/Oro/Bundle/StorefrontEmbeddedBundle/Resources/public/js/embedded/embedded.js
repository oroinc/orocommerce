import {installRequestProxy} from './patches/request-proxy';
import {installModalEmbeddedExtend} from './patches/modal-embedded-extend';
import {installDialogEmbeddedExtend} from './patches/dialog-embedded-extend';
import {installDropdownEmbeddedExtend} from './patches/dropdown-embedded-extend';
import {installDomRelocationEmbeddedExtend} from './patches/dom-relocation-embedded-extend';
import DomRelocationView from 'orofrontend/js/app/views/dom-relocation-view';
import {installLayoutSubtreeEmbeddedExtend} from './patches/layout-subtree-embedded-extend';
import {installTimepickerEmbeddedExtend} from './patches/timepicker-embedded-extend';
import {installViewportEmbeddedExtend} from './patches/viewport-embedded-extend';
import {installTooltipEmbeddedExtend} from './patches/tooltip-embedded-extend';
import {installDatepickerEmbeddedExtend} from './patches/datepicker-embedded-extend';
import {installFullscreenPopupEmbeddedExtend} from './patches/fullscreen-popup-embedded-extend';
import {installLoadingMaskEmbeddedExtend} from './patches/loading-mask-embedded-extend';
import {installPrintPageEmbeddedExtend} from './patches/print-page-embedded-extend';
import {installBodyScrollLockerEmbeddedExtend} from './patches/body-scroll-locker-embedded-extend';
import {registerEmbeddedHandlers} from './embedded-handlers';
import {proxyLoaderResources} from './loader-resources';

let bootPromise = null;
let runtimePatched = false;

export function bootEmbeddedApp() {
    if (!bootPromise) {
        bootPromise = boot();
        bootPromise.catch(() => {
            bootPromise = null;
        });
    }

    return bootPromise;
}

async function boot() {
    installSchedulerYieldFallback();

    await exposeModuleLoader();
    proxyLoaderResources();
    await Promise.all([
        import('oronavigation/js/routes-loader'),
        import('orotranslation/js/translation-loader')
    ]);

    if (!runtimePatched) {
        patchRuntimeBeforeAppModules();
    }

    await runAppModules();

    if (!runtimePatched) {
        runtimePatched = true;
        patchRuntimeAfterAppModules();
    }

    registerEmbeddedHandlers();
}

function patchRuntimeBeforeAppModules() {
    installBodyScrollLockerEmbeddedExtend();
    installRequestProxy();
    installViewportEmbeddedExtend();
}

function patchRuntimeAfterAppModules() {
    installModalEmbeddedExtend();
    installDialogEmbeddedExtend();
    installDropdownEmbeddedExtend();
    installTooltipEmbeddedExtend();
    installDatepickerEmbeddedExtend();
    installLoadingMaskEmbeddedExtend();
    installPrintPageEmbeddedExtend();
    installTimepickerEmbeddedExtend();
    installLayoutSubtreeEmbeddedExtend();
    installFullscreenPopupEmbeddedExtend();
    installDomRelocationEmbeddedExtend();
    startDomRelocation();
}

function installSchedulerYieldFallback() {
    if (!window.scheduler) {
        window.scheduler = {};
    }
    if (!window.scheduler.yield) {
        window.scheduler.yield = () => new Promise(resolve => setTimeout(resolve, 0));
    }
}

async function exposeModuleLoader() {
    const {default: loadModules} = await import('oroui/js/app/services/load-modules');
    window.loadModules = loadModules;
}

async function runAppModules() {
    const {default: appModulePromises} = await import('app-modules');
    await Promise.all(appModulePromises);
}

function startDomRelocation() {
    new DomRelocationView();
}
