import $ from 'jquery';
import Translator from 'orotranslation/lib/translator';
import NumberFormatter from 'orolocale/js/formatter/number';
import Popover from 'oroui/js/extend/bootstrap/bootstrap-popover';
import ListItemProductPricesView from 'oropricing/js/app/views/list-item-product-prices-view';

Translator.fromJSON({
    locale: 'en',
    defaultDomain: 'jsmessages',
    translations: {
        en: {
            jsmessages: {
                'oro.pricing.qty': 'Qty',
                'oro.pricing.unit_price': 'Unit Price',
                'oro.pricing.product_prices.price_not_found': 'Price not found',
                'oro.product.product_unit.item.label.full': 'item',
                'oro.product.product_unit.set.label.full': 'set'
            }
        }
    }
});

describe('oropricing/js/app/views/list-item-product-prices-view', () => {
    let view;
    let $el;

    const prices = [
        {unit: 'item', quantity: 1, price: 10, currency: 'USD'},
        {unit: 'item', quantity: 5, price: 8, currency: 'USD'}
    ];

    const createView = options => new ListItemProductPricesView({
        el: $el,
        showValuePrice: false,
        showListedPrice: false,
        showHint: true,
        doUpdateQtyForUnit: false,
        modelAttr: {prices, unit: 'item'},
        ...options
    });

    beforeEach(() => {
        view = null;
        spyOn(NumberFormatter, 'formatCurrency').and.returnValue('$0.00');

        $el = $(
            '<div>' +
                '<div data-name="prices"></div>' +
                '<button type="button" data-name="prices-hint-trigger"></button>' +
            '</div>'
        ).appendTo(document.body);
    });

    afterEach(() => {
        if (view) {
            const $trigger = view.getElement('pricesHint');
            if ($trigger.data(Popover.DATA_KEY)) {
                $trigger.popover('dispose');
            }
            view.dispose();
        }
        $el.remove();
    });

    describe('price hint deferred initialization', () => {
        it('does not initialize the hint popover on render', () => {
            view = createView();

            const $trigger = view.getElement('pricesHint');
            expect($trigger.data(Popover.DATA_KEY)).toBeUndefined();
            expect($trigger.attr('data-content')).toBeUndefined();
        });

        it('does not initialize the hint popover when a unit change is handled before the hint is opened', () => {
            view = createView({doUpdateQtyForUnit: true});

            view.model.set('unit', 'set');

            expect(view.getElement('pricesHint').data(Popover.DATA_KEY)).toBeUndefined();
        });

        it('initializes and opens the hint popover on first click', () => {
            view = createView();

            const $trigger = view.getElement('pricesHint');
            $trigger.trigger('click');

            const popover = $trigger.data(Popover.DATA_KEY);
            expect(popover).toBeDefined();
            expect($trigger.attr('data-content')).toBeTruthy();
            expect(popover.isOpen()).toBe(true);
        });

        it('refreshes the hint content when the unit changes after the hint was opened', () => {
            view = createView();

            const $trigger = view.getElement('pricesHint');
            $trigger.trigger('click');
            expect($trigger.attr('data-content')).toContain('>item<');

            view.model.set('unit', 'set');

            expect($trigger.attr('data-content')).toContain('>set<');
        });
    });
});
