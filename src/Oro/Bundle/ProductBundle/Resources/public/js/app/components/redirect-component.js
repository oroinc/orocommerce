import BaseComponent from 'oroui/js/app/components/base/component';
import mediator from 'oroui/js/mediator';

const RedirectComponent = BaseComponent.extend({
    /**
     * @property {Object}
     */
    options: {
        targetUrl: null
    },

    /**
     * @inheritdoc
     */
    constructor: function RedirectComponent(options) {
        RedirectComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        const targetUrl = options.targetUrl || null;
        this.redirectTo(targetUrl);
    },

    redirectTo: function(targetUrl) {
        if (targetUrl) {
            mediator.execute('redirectTo', {url: targetUrl}, {redirect: true});
        }
    }
});

export default RedirectComponent;
