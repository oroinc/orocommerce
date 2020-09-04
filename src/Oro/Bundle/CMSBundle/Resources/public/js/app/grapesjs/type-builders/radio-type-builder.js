import _ from 'underscore';
import $ from 'jquery';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const RadioTypeBuilder = BaseTypeBuilder.extend({
    componentType: 'radio',

    constructor: function RadioTypeBuilder(options) {
        RadioTypeBuilder.__super__.constructor.call(this, options);
    },

    initialize(options) {
        Object.assign(this, _.pick(options, 'editor'));
    },

    execute() {
        const original = this.editor.StyleManager.getType(this.componentType).view.prototype.onRender;

        this.editor.StyleManager.addType(this.componentType, {
            view: {
                onRender() {
                    original.call(this);

                    // Generate unique IDs for repeated elements
                    this.$el.find('[for]').each((i, el) => {
                        const $el = $(el);
                        const $rel = $el.prev();
                        let id = $el.attr('for');

                        id += `-${this.cid}`;

                        $el.attr('for', id);
                        $rel.attr('id', id);
                    });
                }
            }
        });
    }
});

export default RadioTypeBuilder;
