import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const ColumnTypeBuilder = BaseTypeBuilder.extend({
    constructor: function ColumnTypeBuilder(options) {
        ColumnTypeBuilder.__super__.constructor.call(this, options);
    },

    editorEvents: {
        'selector:add': 'onSelectorAdd'
    },

    modelMixin: {
        defaults: {
            classes: ['grid-cell'],
            draggable: '.grid-row',
            resizable: {
                tl: 0,
                tc: 0,
                tr: 0,
                bl: 0,
                br: 0,
                bc: 0,
                minDim: 10,
                maxDim: 90,
                step: 0.2,
                currentUnit: 0,
                unitWidth: '%',
                updateTarget(target, {w, h}, {config, resizer}) {
                    const model = target.__cashData.model;
                    const siblingMethod = resizer.handlerAttr === 'cr' ? 'nextSibling' : 'previousSibling';
                    const sibling = target[siblingMethod] ? target[siblingMethod].__cashData.model : false;

                    const getWidth = m => parseFloat(m.getStyle()[config.keyWidth].replace(config.unitWidth, ''));

                    if (sibling) {
                        const originSize = getWidth(model) + getWidth(sibling);
                        if (originSize - w < resizer.opts.minDim) {
                            return;
                        }
                        sibling.setStyle({
                            [config.keyWidth]: originSize - w + config.unitWidth
                        });
                    }

                    model.setStyle({
                        [config.keyWidth]: w + config.unitWidth
                    });
                }
            }
        },

        clone(...args) {
            const {unitWidth} = this.get('resizable');
            const cloned = this.constructor.__super__.clone.apply(this, args);

            const originWidth = this.getStyle().width;

            if (originWidth) {
                this.setStyle({
                    width: parseFloat(originWidth) / 2 + unitWidth
                });

                cloned.setStyle({
                    width: parseFloat(originWidth) / 2 + unitWidth
                });
            }

            return cloned;
        },

        remove(...args) {
            const {unitWidth} = this.get('resizable');
            const originWidth = parseFloat(this.getStyle().width.replace(unitWidth, ''));
            const index = this.collection.findIndex(model => model.cid === this.cid);
            let referenceModel;

            if (index > 0) {
                referenceModel = this.collection.at(index - 1);
            } else {
                referenceModel = this.collection.at(index + 1);
            }

            const removed = this.constructor.__super__.remove.apply(this, args);

            if (referenceModel) {
                referenceModel.setStyle({
                    width: parseFloat(referenceModel.getStyle().width) + originWidth + unitWidth
                });
            }

            return removed;
        }
    },

    onSelectorAdd(selector) {
        const privateCls = '.grid-cell';
        privateCls.indexOf(selector.getFullName()) >= 0 && selector.set('private', 1);
    },

    isComponent(el) {
        let result = null;

        if (el.tagName === 'DIV' && el.classList.contains('grid-cell')) {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default ColumnTypeBuilder;
