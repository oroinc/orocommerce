import {every} from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orocms/templates/plugins/content-templates/content-templates-panel-view.html';
import HighlightTextView from 'oroui/js/app/views/highlight-text-view';

const ContentTemplatesPanelView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['panelId', 'editor']),

    autoRender: true,

    panelId: null,

    className: 'gjs-wide-blocks content-templates-container',

    template,

    events: {
        'click [data-role="all-trigger"]': 'onAllToggle',
        'input [name="content-template-quick-search"]': 'onSearch'
    },

    listen: {
        'change:allCollapsed model': 'onChangeCollapsedStatus',
        'change:open collection': 'onChangeCategory'
    },

    constructor: function ContentTemplatesPanelView(...args) {
        ContentTemplatesPanelView.__super__.constructor.apply(this, args);
    },

    render() {
        const {Panels, Commands} = this.editor;
        this.subview('highlight', new HighlightTextView({
            el: this.el,
            highlightSelectors: [
                '.gjs-block-label',
                '.gjs-title'
            ],
            toggleSelectors: {
                '.content-template-block': '.gjs-blocks-c',
                '.gjs-block-category': '.gjs-block-categories'
            }
        }));

        ContentTemplatesPanelView.__super__.render.call(this);

        const panels = Panels.getPanel(this.panelId) || Panels.addPanel({
            id: this.panelId
        });

        this.$noMatches = this.$('.no-data');

        if (this.model.getBlockData().length) {
            this.$noMatches.detach();

            const $blocks = this.$('[data-role="blocks"]');
            $blocks.append(this.renderBlocks(this.model.getBlockData()));
            this.$('[data-toggle="tooltip"]').tooltip();

            this.getContentTplCategory().forEach((category, index) => category.set('open', index === 0));

            Commands.run('add-scrolling-hints-to-container', {
                $container: $blocks
            });
        }

        this.$el.inputWidget('seekAndCreate');
        panels.set('appendContent', this.el).trigger('change:appendContent');
    },

    onChangeCategory(model, value, {snapshot}) {
        this.model.set('allCollapsed', every(
            this.getContentTplCategory(),
            model => !model.get('open')
        ));

        if (snapshot) {
            model.set('snapshot', snapshot);
        } else {
            model.get('snapshot') && model.unset('snapshot');
        }
    },

    getContentTplCategory() {
        return this.collection.filter(model => model.get('type') === 'content-templates');
    },

    renderBlocks(dataBlocks = []) {
        const {Blocks} = this.editor;

        return Blocks.render.call({
            ...Blocks,
            categories: {
                categories: this.collection
            }
        }, dataBlocks, {
            external: true
        });
    },

    onChangeCollapsedStatus() {
        const $trigger = this.$('[data-role="all-trigger"]');
        const title = this.model.get('allCollapsed')
            ? __('oro.cms.wysiwyg.content_template_plugin.expand_all')
            : __('oro.cms.wysiwyg.content_template_plugin.collapse_all');

        $trigger.html(
            `<span class="fa fa-caret-${this.model.get('allCollapsed') ? 'right' : 'down'}" aria-hidden="true"></span>`
        );

        $trigger.attr({
            title,
            'data-original-title': title
        });

        $trigger.tooltip('hide');
    },

    beforeSearch(toggle) {
        if (this.searchOn === toggle) {
            return;
        }
        this.searchOn = toggle;

        this.getContentTplCategory().forEach(model => {
            if (toggle) {
                model.set('open', toggle, {
                    snapshot: {
                        open: model.get('open')
                    }
                });
            } else {
                model.set(model.get('snapshot'));
            }
        });
    },

    onSearch(event) {
        this.beforeSearch(!!event.target.value.length);
        this.subview('highlight').update(event.target.value);

        this.toggleNotFoundMessage(!!event.target.value.length &&
            !this.$(this.subview('highlight').findHighlightClass).length);
    },

    onAllToggle() {
        const status = this.model.get('allCollapsed');
        this.getContentTplCategory().forEach(model => model.set('open', status));
    },

    toggleNotFoundMessage(toggle) {
        if (toggle) {
            this.$noMatches.appendTo(this.$('[data-role="panel"]'));
        } else {
            this.$noMatches.detach();
        }

        this.$('[data-role="blocks"]').toggleClass('hide', toggle);
    },

    toggle(show = true) {
        this.$el.toggle(show);
    }
});

export default ContentTemplatesPanelView;
