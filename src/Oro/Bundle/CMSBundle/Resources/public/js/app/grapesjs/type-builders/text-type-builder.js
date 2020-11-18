import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const REGEXP_TAG_EMPTY = /<[^>]*>\s*<\/[^>]*>/g;

const TextTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'text',

    button: {
        label: __('oro.cms.wysiwyg.component.text.label'),
        content: {
            type: 'text',
            content: __('oro.cms.wysiwyg.component.text.content'),
            style: {
                padding: '10px'
            }
        }
    },

    constructor: function TextTypeBuilder(options) {
        TextTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        const tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'ul', 'li', 'ol'];
        let _res = {
            tagName: el.tagName.toLowerCase()
        };

        if (tags.includes(_res.tagName)) {
            _res = {
                ..._res,
                type: 'text'
            };
        }

        return _res;
    },

    viewMixin: {
        /**
         * Disable element content editing
         */
        disableEditing() {
            const {model, rte, activeRte, em} = this;
            if (!model) {
                return;
            }

            const editable = model.get('editable');

            if (rte && editable) {
                try {
                    rte.disable(this, activeRte);
                } catch (err) {
                    em.logError(err);
                }

                this.syncContent();
            }

            this.toggleEvents();
        },

        updateContentText({model, ...args}) {
            if (!model) {
                return;
            }
            this.constructor.__super__.updateContentText.apply(this, [model, ...args]);
        },

        /**
         * Merge content from the DOM to the model
         * @param opts
         */
        syncContent(opts = {}) {
            const tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'];
            const {model, rte, rteEnabled} = this;
            if (!rteEnabled && !opts.force) return;
            let content = this.getContent();
            const comps = model.components();
            const contentOpt = {fromDisable: false, ...opts};
            let tagName = null;
            comps.length && comps.reset(null, opts);
            model.set('content', '', contentOpt);

            // If there is a custom RTE the content is just baked staticly
            // inside 'content'
            if (rte.customRte) {
                model.set('content', content, contentOpt);
            } else {
                const clean = model => {
                    const textable = !!model.get('textable');
                    const selectable =
                        !['text', 'default', ''].some(type => model.is(type)) || textable;

                    model.set({
                        _innertext: !selectable,
                        editable: selectable && model.get('editable'),
                        selectable: selectable,
                        hoverable: selectable,
                        removable: textable,
                        draggable: textable,
                        highlightable: 0,
                        copyable: textable,
                        ...(!textable && {toolbar: ''})
                    }, opts);

                    model.get('components').each(model => clean(model));
                };

                // Avoid re-render on reset with silent option
                !opts.silent && model.trigger('change:content', model, '', contentOpt);

                const el = document.createElement('div');
                el.innerHTML = content;
                if (el.children.length === 1 &&
                    tags.includes(el.children[0].tagName.toLowerCase()) &&
                    tags.includes(model.get('tagName'))) {
                    tagName = el.children[0].tagName.toLowerCase();
                    content = el.children[0].innerHTML;
                }

                // Clear empty tags
                comps.add(content.replace(REGEXP_TAG_EMPTY, ''), opts);

                if (tagName) {
                    this.editor.selectRemove(model);
                    model.set('tagName', tagName);
                    model.trigger('focus');
                    rte.updatePosition();

                    this.editor.select(model);
                }

                comps.each(model => clean(model));
                comps.trigger('resetNavigator');
            }
        }
    }
});

export default TextTypeBuilder;
