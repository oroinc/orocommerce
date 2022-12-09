import _ from 'underscore';
import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import BaseView from 'oroui/js/app/views/base/view';
import messenger from 'oroui/js/messenger';

const QuickAddFormView = BaseView.extend({
    /**
     * @property {Object}
     */
    defaults: {
        componentSelector: '[name$="[component]"]',
        additionalSelector: '[name$="[additional]"]',
        componentButtonSelector: '.component-button',
        componentPrefix: 'quick-add'
    },

    /**
     * @property {QuickAddCollection}
     */
    productsCollection: null,

    events() {
        return {
            [`click ${this.componentButtonSelector}`]: 'fillComponentData'
        };
    },

    listen() {
        return {
            [`${this.componentPrefix}:submit mediator`]: '_submit'
        };
    },

    /**
     * @inheritdoc
     */
    constructor: function QuickAddFormView(options) {
        if (!options.productsCollection) {
            throw new Error('Option `productsCollection` is require for QuickAddCopyPasteFormComponent');
        }
        Object.assign(this, this.defaults,
            _.pick(options, 'productsCollection', ...Object.keys(this.defaults)));
        QuickAddFormView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    delegateEvents(events) {
        QuickAddFormView.__super__.delegateEvents.call(this, events);
        // Handler is moved to parent element to allow preventing submit by validator
        this.$el.parent().on(`submit${this.eventNamespace()}`, this.onSubmit.bind(this));
    },

    /**
     * @inheritdoc
     */
    undelegateEvents() {
        if (this.$el) {
            // this.$el might be not set yet
            this.$el.parent().off(this.eventNamespace());
        }
        QuickAddFormView.__super__.undelegateEvents.call(this);
    },

    fillComponentData(e) {
        const $element = $(e.target);
        this._submit($element.data('component-name'), $element.data('component-additional'));
    },

    /**
     * @param {String} component
     * @param {String} additional
     * @protected
     */
    _submit(component, additional) {
        this.$(this.componentSelector).val(component);
        this.$(this.additionalSelector).val(additional);
        this.$el.submit();
    },

    onSubmit(e) {
        e.preventDefault();

        const formData = new FormData();

        this.$('fieldset[data-name="rest-fields"]')
            .serializeArray()
            .forEach(row => formData.append(row.name, row.value));

        const quickAddRows = this.productsCollection.map(model => model.toBackendJSON())
            .filter(item => item.sku !== '');
        formData.append(`${this.$el.attr('name')}[products]`, JSON.stringify(quickAddRows));

        $.ajax({
            type: 'POST',
            url: this.$el.attr('action'),
            contentType: false,
            beforeSend(xhr, options) {
                options.data = formData;
            },
            success: response => {
                if (response.hasOwnProperty('redirectUrl')) {
                    mediator.execute('redirectTo', {url: response.redirectUrl}, {redirect: true});
                    return;
                }

                if (response.success) {
                    mediator.trigger('shopping-list:refresh');
                }

                if (response.messages) {
                    Object.entries(response.messages).forEach(([type, messages]) => {
                        messages.forEach(message => messenger.notificationMessage(type, message));
                    });
                }

                if (response?.collection?.errors) {
                    Object.values(response.collection.errors)
                        .forEach(error => messenger.notificationMessage('error', error.message));
                }

                if (response?.collection?.items) {
                    this.productsCollection
                        .addQuickAddRows(response.collection.items, {strategy: 'replace'});
                }
            }
        });
    }
});

export default QuickAddFormView;
