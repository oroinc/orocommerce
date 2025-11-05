import BaseView from 'oroui/js/app/views/base/view';
import _ from 'underscore';
import $ from 'jquery';
import ShoppingListCollectionService from 'oroshoppinglist/js/shoppinglist-collection-service';

const ShoppingListWidgetView = BaseView.extend({
    options: {
        currentClass: ''
    },

    /**
     * Backbone.Collection {Object}
     */
    shoppingListCollection: null,

    $dropdown: null,

    listen: {
        'layout:reposition mediator': 'updateDropdown'
    },

    /**
     * @inheritdoc
     */
    constructor: function ShoppingListWidgetView(options) {
        this.updateDropdown = _.debounce(this.updateDropdown.bind(this));
        ShoppingListWidgetView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        ShoppingListWidgetView.__super__.initialize.call(this, options);

        this.options = _.defaults(options || {}, this.options);
        this.$el = $(this.options._sourceElement);
        this.$dropdown = this.$el.closest('.shopping-list-widget');
        this.$dropdown.on(`shown.bs.dropdown${this.eventNamespace()}`, this.updateDropdown.bind(this));
        this.$dropdown.on(`hide.bs.dropdown${this.eventNamespace()}`, this.hideDropdown.bind(this));

        ShoppingListCollectionService.shoppingListCollection.done(collection => {
            this.shoppingListCollection = collection;
            this.listenTo(collection, 'change', this.render);
            this.render();
        });
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.$dropdown.off(this.eventNamespace());

        if (_.isMobile()) {
            document.body.classList.remove('no-scroll');
        }

        delete this.shoppingListCollection;
        return ShoppingListWidgetView.__super__.dispose.call(this);
    },

    render: function() {
        const $shoppingListWidget = this.$el.closest('.shopping-list-widget');
        const showShoppingListDropdown =
            this.shoppingListCollection.length ||
            $shoppingListWidget.find('[data-shopping-list-create]').length;

        $shoppingListWidget.toggleClass(
            'shopping-list-widget--disabled',
            !showShoppingListDropdown
        );

        $shoppingListWidget.find('.shopping-list-trigger')
            .toggleClass('disabled', !showShoppingListDropdown)
            .attr('disabled', !showShoppingListDropdown);

        this.$('[data-role="set-default"]:checked').closest('.shopping-list-dropdown__item')
            .addClass('shopping-list-dropdown__item--default')
            .siblings()
            .removeClass('shopping-list-dropdown__item--default');
    },

    hideDropdown() {
        if (_.isMobile()) {
            document.body.classList.remove('no-scroll');
        }
    },

    updateDropdown() {
        if (_.isMobile() && this.$el.closest('.show').length) {
            document.body.classList.add('no-scroll');
        }

        const $container = this.$el.closest('[data-header-row-toggle]');

        if ($container.get(0)) {
            $container.css('--shopping-list-widget-top', $container.get(0).getBoundingClientRect().top + 'px');
            $container.css('--shopping-list-visible-viewport-height', visualViewport.height + 'px');
        }
    }
});

export default ShoppingListWidgetView;
