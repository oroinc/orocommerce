import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import Chaplin from 'chaplin';
import mediator from 'oroui/js/mediator';
import moduleConfig from 'module-config';
import formToAjaxOptions from 'oroui/js/tools/form-to-ajax-options';
import BaseView from 'oroui/js/app/views/base/view';
import {navigateFragmentTo, isAnotherHostPage} from './host';
import {isCurrentPath, toHostPath, toInternalPath, withQuery} from './tools/url';
import {HASH_NAVIGATION_HEADER} from './config';

const config = {
    skipRouting: '.noscript',
    ...moduleConfig(module.id)
};

const utils = Chaplin.utils;
const LEFT_MOUSE_BUTTON = 0;

const EmbeddedFragmentView = BaseView.extend({
    autoRender: false,
    keepElement: true,

    skipRouting: config.skipRouting,

    events: {
        'submit form': 'onSubmit',
        'click a[href]': 'onLink'
    },

    constructor: function EmbeddedFragmentView(options) {
        this.fragment = options.fragment;
        EmbeddedFragmentView.__super__.constructor.call(this, options);
    },

    onLink(event) {
        const anchor = event.currentTarget;
        if (this.isHandledByBrowser(event, anchor)) {
            return;
        }

        const href = anchor.getAttribute('href') || anchor.getAttribute('data-href') || '';
        if (href === '#') {
            event.preventDefault();
            return;
        }
        const target = toInternalPath(href);
        if (!target || this.shouldSkipRouting(href, anchor)) {
            return;
        }

        event.preventDefault();
        if (this.isOpenLinkPrevented(anchor)) {
            return;
        }

        this.goTo(target);
    },

    isHandledByBrowser(event, anchor) {
        return event.isDefaultPrevented() ||
            event.button !== LEFT_MOUSE_BUTTON ||
            utils.modifierKeyPressed(event) ||
            (Boolean(anchor.target) && anchor.target !== '_self') ||
            anchor.hasAttribute('download');
    },

    shouldSkipRouting(href, anchor) {
        switch (typeof this.skipRouting) {
            case 'function':
                return !this.skipRouting(href, anchor);
            case 'string':
                return Boolean(this.skipRouting) && utils.matchesSelector(anchor, this.skipRouting);
            default:
                return false;
        }
    },

    isOpenLinkPrevented(anchor) {
        const payload = {prevented: false, target: anchor};
        mediator.trigger('openLink:before', payload);

        return payload.prevented !== false;
    },

    onSubmit(event) {
        const $form = $(event.currentTarget);
        if ($form.is('[data-prevent-submit]')) {
            event.preventDefault();
            return;
        }
        if (event.isDefaultPrevented() || $form.data('nohash')) {
            return;
        }

        const action = toInternalPath($form.attr('action') || window.location.pathname + window.location.search);
        if (!action) {
            return;
        }

        event.preventDefault();

        if (($form.attr('method') || 'get').toLowerCase() === 'post') {
            this.submitForm($form, event.submitter ?? event.originalEvent?.submitter ?? null);
            return;
        }

        this.goTo(withQuery(action, $form.serialize()));
    },

    goTo(target) {
        if (isCurrentPath(toHostPath(target))) {
            this.fragment.loadingMask.show();

            return this.fragment.reload()
                .catch(() => this.reportFailure())
                .finally(() => this.hideLoadingMask());
        }

        const isLeavingPage = navigateFragmentTo(this.fragment, target);

        if (isLeavingPage || isAnotherHostPage(target)) {
            this.fragment.loadingMask.show();
        } else {
            this.hideLoadingMask();
        }

        return null;
    },

    submitForm($form, submitter) {
        if ($form.data('sent')) {
            return;
        }
        $form.data('sent', true);
        this.fragment.loadingMask.show();

        this.waitForBeforeSubmitHandlers()
            .then(() => this.sendForm($form, submitter))
            .then(response => this.applySubmitResponse(response))
            .catch(() => this.reportFailure())
            .finally(() => $form.removeData('sent'));
    },

    sendForm($form, submitter) {
        if (this.disposed) {
            return null;
        }
        this.beforeSerializeHook($form);

        return mediator.execute('submitPage', this.buildAjaxOptions($form, submitter));
    },

    applySubmitResponse(response) {
        if (this.disposed || !response) {
            return null;
        }
        if (response.redirect) {
            return this.followSaveRedirect(response.location);
        }

        return this.fragment.setContent(response[this.fragment.block])
            .then(() => this.hideLoadingMask());
    },

    reportFailure() {
        this.hideLoadingMask();
        mediator.execute('showErrorMessage', __('oro.ui.unexpected_error'));
    },

    hideLoadingMask() {
        if (!this.disposed) {
            this.fragment.loadingMask.hide();
        }
    },

    waitForBeforeSubmitHandlers() {
        const queue = [];
        mediator.trigger('before:submitPage', queue);

        return Promise.all(queue);
    },

    beforeSerializeHook($form) {
        this.syncDependentHiddenInput($form);
    },

    syncDependentHiddenInput($form) {
        for (const checkbox of $form.find('input[type="checkbox"]')) {
            const hiddenField = checkbox.id ? document.getElementById(`${checkbox.id}-hidden`) : null;

            if (hiddenField && hiddenField.name === checkbox.name) {
                hiddenField.disabled = checkbox.checked;
            }
        }
    },

    buildAjaxOptions($form, submitter) {
        return formToAjaxOptions($form, {
            url: this.fragment.endpoint,
            data: submitter?.name ? {[submitter.name]: submitter.value} : undefined,
            dataType: 'json',
            headers: {[HASH_NAVIGATION_HEADER]: true}
        });
    },

    followSaveRedirect(location) {
        return this.goTo(toHostPath(location));
    }
});

export default EmbeddedFragmentView;
