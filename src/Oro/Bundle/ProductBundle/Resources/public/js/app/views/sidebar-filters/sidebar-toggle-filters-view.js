import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import routing from 'routing';
import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!oroproduct/templates/sidebar-filters/sidebar-toggle-filters-view.html';

import moduleConfig from 'module-config';

const COLLAPSED = 'collapsed';
const EXPANDED = 'expanded';

const config = {
    animationDuration: 150,
    className: 'toggle-sidebar-btn btn btn--default btn--size-s',
    [`${EXPANDED}Title`]: 'oro.product.sidebar_filters.button.title.collapse',
    [`${EXPANDED}Icon`]: 'fa-chevron-left fa--no-offset',
    [`${COLLAPSED}Title`]: 'oro.product.sidebar_filters.button.title.expand',
    [`${COLLAPSED}Icon`]: 'fa-filter fa--no-offset',
    ...moduleConfig(module.id)
};

const SidebarToggleFiltersView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        '$content',
        '$sidebar',
        'routeName',
        'sidebarExpanded'
    ]),

    autoAttach: true,

    noWrap: true,

    template: template,

    routeName: 'oro_product_frontend_ajax_set_product_filters_sidebar_state',

    sidebarExpanded: true,

    animationDuration: config.animationDuration,

    events: {
        click: 'onClick'
    },

    constructor: function SidebarToggleFiltersView(options) {
        SidebarToggleFiltersView.__super__.constructor.call(this, options);
    },

    onClick() {
        this.toggleSidebar();
    },

    getDataToSave() {
        return {
            sidebarExpanded: this.sidebarExpanded ? 1 : 0
        };
    },

    saveState() {
        this.disable();

        $.ajax({
            method: 'POST',
            type: 'json',
            url: routing.generate(this.routeName),
            data: this.getDataToSave(),
            complete: () => {
                this.render();
            }
        });
    },

    disable() {
        this.$el.attr({
            'disabled': true,
            'data-has-focus': this.hasFocus()
        });

        return this;
    },

    hasFocus() {
        return document.activeElement.isSameNode(this.el);
    },

    render() {
        const $oldEl = this.$el;
        const hasFocus = this.hasFocus();

        SidebarToggleFiltersView.__super__.render.call(this);

        // Re-rendering process
        if (document.contains($oldEl[0])) {
            // replace old element and keep original place in DOM
            $oldEl.replaceWith(this.el);
        }

        // None specific focus target was established,
        // therefore restore focus on element
        if (
            ($oldEl.data('has-focus') || hasFocus) &&
            $(document.activeElement).is('body')
        ) {
            this.$el.focus();
        }

        this.$el.toggleClass('is-expanded', this.sidebarExpanded);

        return this;
    },

    /**
     * @inheritdoc
     */
    getTemplateData: function() {
        const data = SidebarToggleFiltersView.__super__.getTemplateData.call(this);
        const stateKey = this.sidebarExpanded ? EXPANDED : COLLAPSED;

        data.className = config.className;
        data.icon = config[`${stateKey}Icon`];
        data.title = config[`${stateKey}Title`];

        return data;
    },

    toggleSidebar(e) {
        if (this.sidebarExpanded) {
            this.collapse();
        } else {
            this.expand();
        }

        this.render();
        this.saveState();
    },

    collapse(duration = this.animationDuration) {
        this.sidebarExpanded = false;
        this.doCollapseAnimation(duration);
    },

    expand(duration = this.animationDuration) {
        this.sidebarExpanded = true;
        this.doExpandAnimation(duration);
    },

    doAnimation(doDesignFn, duration = 250) {
        const start = performance.now();

        return new Promise(resolve => {
            if (!duration) {
                return resolve();
            }

            requestAnimationFrame(function animate(time) {
                let progress = (performance.now() - start) / duration;

                if (progress > 1) {
                    progress = 1;
                }

                doDesignFn(progress);

                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    resolve();
                }
            });
        });
    },

    doCollapseAnimation(duration) {
        this.$content.css('will-change', 'width');
        this.$sidebar.css({
            'will-change': 'width, opacity',
            'overflow': 'hidden'
        }).children().each((i, el) => {
            $(el).css('width', $(el).outerWidth());
        });

        const originalW = parseInt(window.getComputedStyle(this.$sidebar[0]).getPropertyValue('width'));
        this.trigger('toggle-sidebar:before-collapse');
        this.doAnimation(progress => {
            const delta = originalW * progress;

            this.$sidebar.css({
                opacity: 1 - progress,
                width: originalW - delta
            });
            this.$content.css('width', `calc(100% - ${originalW - delta}px)`);
        }, duration).then(() => {
            this.$sidebar.addClass('hidden').css({
                'width': '',
                'will-change': '',
                'overflow': '',
                'opacity': ''
            }).children().css('width', '');
            this.$content.css({
                'width': '',
                'will-change': ''
            }).removeClass('page-content--has-sidebar');
            this.trigger('toggle-sidebar:after-collapse');
            mediator.trigger('toggle-sidebar', {expanded: false});
        });
    },

    doExpandAnimation(duration) {
        const originalW = parseInt(window.getComputedStyle(this.$sidebar[0]).getPropertyValue('width'));

        this.$content.css({
            'will-change': 'width',
            'width': 'calc(100% - 1px)'
        });
        this.$sidebar.css({
            'will-change': 'width, opacity',
            'overflow': 'hidden',
            'width': '1px',
            'opacity': 0
        }).removeClass('hidden').children().each((i, el) => {
            $(el).css('width', originalW);
        });
        this.trigger('toggle-sidebar:before-expand');
        this.doAnimation(progress => {
            const delta = originalW * progress;

            this.$content.css('width', `calc(100% - ${delta}px)`);
            this.$sidebar.css({
                opacity: progress,
                width: delta
            });
        }, duration).then(() => {
            this.$sidebar.css({
                'width': '',
                'will-change': '',
                'overflow': '',
                'opacity': ''
            }).children().css('width', '');
            this.$content.css({
                'width': '',
                'will-change': ''
            }).addClass('page-content--has-sidebar');
            this.trigger('toggle-sidebar:after-expand');
            mediator.trigger('toggle-sidebar', {expanded: true});
        });
    }
});

export default SidebarToggleFiltersView;
