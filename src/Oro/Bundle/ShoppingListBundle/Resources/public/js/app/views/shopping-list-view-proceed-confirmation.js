import routing from 'routing';
import mediator from 'oroui/js/mediator';
import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';
import StandartConfirmation from 'oroui/js/standart-confirmation';

const ShoppingListViewProceedConfirmation = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'invalidIds', 'route', 'routeParams',
        'modalContent', 'modalTitle', 'okText', 'cancelText'
    ]),

    route: null,

    modalContent: null,

    modalTitle: null,

    okText: null,

    cancelText: __('Cancel'),

    attributes: {
        type: 'button'
    },

    events() {
        const events = {};

        if (this.invalidIds.length) {
            events['click'] = 'showConfirmation';
        }

        return events;
    },

    /**
     * @inheritdoc
     */
    constructor: function ShoppingListViewProceedConfirmation(...args) {
        ShoppingListViewProceedConfirmation.__super__.constructor.apply(this, args);
    },

    getUrl() {
        return routing.generate(this.route, this.routeParams);
    },

    getModelParams() {
        return {
            okText: __(this.okText),
            cancelText: __(this.cancelText),
            content: __(this.modalContent),
            title: __(this.modalTitle)
        };
    },

    showConfirmation() {
        this.subview('confirmation', new StandartConfirmation(this.getModelParams()));
        this.listenTo(this.subview('confirmation'), 'ok', () => {
            mediator.execute('showLoading');
            mediator.execute('redirectTo', {
                url: this.getUrl()
            });
        });
        this.subview('confirmation').open();
    }
});

export default ShoppingListViewProceedConfirmation;
