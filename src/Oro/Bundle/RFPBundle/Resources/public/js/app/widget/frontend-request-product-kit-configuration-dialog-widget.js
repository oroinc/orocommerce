import FrontendDialogWidget from 'oro/dialog-widget';
import messenger from 'oroui/js/messenger';
import _ from 'underscore';
import $ from 'jquery';
import 'jquery.validate';

const FrontendRequestProductKitConfigurationDialogWidget = FrontendDialogWidget.extend({
    options: _.extend({}, FrontendDialogWidget.prototype.options, {
        moveAdoptedActions: false,
        preventModelRemoval: true,
        incrementalPosition: false,
        data: null,
        dialogOptions: {
            modal: true,
            resizable: false,
            width: 890,
            minWidth: 367,
            maxWidth: 'auto',
            autoResize: true,
            dialogClass: 'product-kit-dialog'
        }
    }),

    isWidgetInit: true,

    NAME: 'request-product-kit-configuration-dialog',

    constructor: function FrontendRequestProductKitConfigurationDialogWidget(options) {
        FrontendRequestProductKitConfigurationDialogWidget.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    loadContent: function(data, method, url) {
        if (data === undefined) {
            data = this.options.data ? $.param(this.options.data) : undefined;
            this.isWidgetInit = true;
        } else {
            this.isWidgetInit = false;
        }

        return FrontendRequestProductKitConfigurationDialogWidget.__super__.loadContent.call(this, data, method, url);
    },

    /**
     * @inheritdoc
     */
    _onContentLoad: function(content, textStatus, jqXHR) {
        if (jqXHR !== undefined && jqXHR.status === 200 && !this.isWidgetInit) {
            this.trigger('success', content);
        } else {
            FrontendRequestProductKitConfigurationDialogWidget.__super__._onContentLoad.call(this, content);
        }
    },

    /**
     * @inheritdoc
     */
    _onContentLoadFail(jqXHR, textStatus, errorThrown) {
        if (jqXHR.responseJSON) {
            this._onJsonContentResponse(jqXHR.responseJSON);
        } else if (jqXHR.status === 422) {
            FrontendRequestProductKitConfigurationDialogWidget.__super__._onContentLoad.call(
                this,
                jqXHR.responseText,
                textStatus,
                jqXHR
            );
        } else {
            FrontendRequestProductKitConfigurationDialogWidget.__super__._onContentLoadFail.call(
                this,
                jqXHR,
                textStatus,
                errorThrown
            );
        }
    },

    /**
     * @inheritdoc
     */
    _onJsonContentResponse(response) {
        if (response.messages) {
            Object.entries(response.messages).forEach(([type, messages]) => {
                messages.forEach(message => messenger.notificationMessage(type, message));
            });
        }

        this.remove();
    },

    /**
     * @inheritdoc
     */
    _renderActions() {
        this._clearActionsContainer();

        const container = this.getActionsElement();
        const adoptedActionsContainer = this._getAdoptedActionsContainer();

        if (container.length > 0 && adoptedActionsContainer?.length > 0) {
            adoptedActionsContainer.find('>').appendTo(container);
            adoptedActionsContainer.remove();

            _.each(this.actions, (actions, section) => {
                _.each(actions, (action, key) => {
                    $(action).attr('form', this.form.attr('id'));
                    this._initActionEvents(action);
                    this.trigger('widget:add:action:' + section + ':' + key, $(action));
                });
            });

            this.widget.dialog('showActionsContainer');
        }
    }
});

export default FrontendRequestProductKitConfigurationDialogWidget;
