import __ from 'orotranslation/js/translator';

export default (BaseTypeModel, {editor}) => {
    const TextTypeModel = BaseTypeModel.extend({
        editor,

        constructor: function TextTypeModel(...args) {
            return TextTypeModel.__super__.constructor.apply(this, args);
        },

        init() {
            const components = this.get('components');

            if (!components.length && !this.get('wrapping')) {
                components.add([{
                    type: 'textnode',
                    content: __('oro.cms.wysiwyg.component.text.content')
                }]);
            }

            this.on('sync:content', this.syncContent.bind(this));
        },

        replaceWith(el, updateStyle = true) {
            const styles = this.getStyle();
            const classes = this.getClasses();
            const newModels = TextTypeModel.__super__.replaceWith.call(this, el);

            if (updateStyle) {
                newModels.forEach(model => {
                    model.setStyle(styles);
                    model.setClass(classes);
                });
            }

            return newModels;
        },

        setContent(content, options = {}) {
            this.components(content, options);
            this.syncContent();
        },

        syncContent() {
            this.view.syncContent({
                force: true
            });
        },

        getAttrToHTML() {
            const attrs = this.getAttributes();

            if (!attrs.style) {
                delete attrs.style;
            }

            return attrs;
        },

        attrUpdated(m, v, opts = {}) {
            const {shallowDiff} = this.em.get('Utils').helpers;
            const attrs = this.get('attributes');
            // Handle classes
            const classes = attrs.class;
            classes && this.setClass(classes);
            delete attrs.class;

            const attrPrev = {
                ...this.previous('attributes')
            };
            const diff = shallowDiff(attrPrev, this.get('attributes'));
            Object.keys(diff).forEach(pr =>
                this.trigger(`change:attributes:${pr}`, this, diff[pr], opts)
            );
        }
    });

    return TextTypeModel;
};
