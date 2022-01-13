import {isMatch, omit} from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const PictureTypeBuilder = BaseTypeBuilder.extend({
    button: {
        label: __('oro.cms.wysiwyg.component.digital_asset.image'),
        category: 'Basic',
        activate: true,
        attributes: {
            'class': 'fa fa-picture-o'
        },
        options: {
            at: 6
        }
    },

    modelMixin: {
        defaults: {
            tagName: 'picture',
            type: 'picture',
            sources: [],
            editable: true,
            droppable: false,
            resizable: {
                ratioDefault: true
            }
        },

        initialize(...args) {
            this.defaults.resizable['onUpdateContainer'] = () => {
                const {width = '', height = ''} = this.getStyle();
                this.image.setStyle({
                    width,
                    height
                });
            };

            this.constructor.__super__.initialize.apply(this, args);

            const components = this.get('components');
            if (!components.length) {
                components.add({
                    type: 'image'
                });
            }

            this.image = this.findType('image')[0];
            this.image.set({
                selectable: 0,
                hoverable: 0
            });

            this.listenTo(this.image, 'change:previewMetadata', this.onImageUpdate);
            this.listenTo(this, 'change:sources', this.updateSources);
        },

        setSource(key, props) {
            const sources = this.get('sources');
            const findIndex = sources.findIndex(({key: sourceKey, attributes}) => {
                if (!sourceKey) {
                    return isMatch(omit(attributes, 'srcset'), omit(props, 'srcset'));
                }
                return sourceKey === key;
            });

            if (findIndex !== -1) {
                sources[findIndex] = {key, attributes: props};
            } else {
                sources.push({key, attributes: props});
            }

            this.set('sources', sources);
            this.trigger('change:sources');
        },

        onImageUpdate(image, {url_webp: srcset}) {
            this.setSource('webp', {
                srcset,
                type: 'image/webp'
            });
        },

        updateSources() {
            this.findType('source').forEach(source => source.remove());
            this.append(this.get('sources').map(({key, attributes}) => {
                return {
                    type: 'source',
                    attributes,
                    key
                };
            }), {
                at: 0
            });
        }
    },

    viewMixin: {
        onActive(e) {
            setTimeout(() => {
                this.model.image.view.onActive(e);
            }, 200);
        },
        onRender() {
            // Need for correct work picture tag wrapper with resize and selection
            this.$el.css('display', 'inline-block');
        }
    },

    constructor: function PictureTypeBuilder(options) {
        PictureTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        if (el.nodeType === 1 && el.tagName.toLowerCase() === 'picture') {
            return {
                type: this.componentType,
                sources: [...el.querySelectorAll('source')].map(source => {
                    return {
                        attributes: [...source.attributes].reduce((attrs, attribute) => {
                            attrs[attribute.name] = attribute.value;
                            return attrs;
                        }, {})
                    };
                })
            };
        }
    }
});

export default PictureTypeBuilder;
