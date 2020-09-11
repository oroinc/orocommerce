import _ from 'underscore';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const VideoTypeBuilder = BaseTypeBuilder.extend({
    componentType: 'video',

    constructor: function VideoTypeBuilder(options) {
        VideoTypeBuilder.__super__.constructor.call(this, options);
    },

    initialize(options) {
        Object.assign(this, _.pick(options, 'editor'));
    },

    execute() {
        const componentRestriction = this.editor.ComponentRestriction;
        const {BlockManager} = this.editor;

        this.editor.DomComponents.addType(this.componentType, {
            model: {
                defaults: {
                    classes: ['video-container']
                },

                getProviderTrait() {
                    const providerTrait = this.constructor.__super__.getProviderTrait.call(this);
                    const options = providerTrait.options
                        .filter(prov => componentRestriction.isAllow(this.getTagNameByProvider(prov.value)))
                        .filter(prov => {
                            // Skip source provider
                            if (prov.value === 'so') {
                                return true;
                            }

                            const providerUrl = this.getUrlByProvider(prov.value);

                            if (providerUrl === null) {
                                return false;
                            }

                            return componentRestriction.isAllowedDomain(providerUrl);
                        });

                    return {
                        ...providerTrait,
                        options
                    };
                },

                getUrlByProvider(provider) {
                    return this.get(`${provider}Url`) || null;
                },

                getTagNameByProvider(provider) {
                    switch (provider) {
                        case 'yt':
                        case 'ytnc':
                        case 'vi':
                            return 'iframe';
                        default:
                            return 'video';
                    }
                },

                getSourceTraits() {
                    const sourceTrait = this.constructor.__super__.getSourceTraits.call(this);
                    // keep poster's trait value in properties,
                    // and to save values into attributes only common for all sources, e.g. src, id
                    sourceTrait.find(source => source.name === 'poster').changeProp = 1;
                    return sourceTrait;
                },

                getAttrToHTML() {
                    const attr = this.constructor.__super__.getAttrToHTML.call(this);
                    const tagName = this.getTagNameByProvider(this.get('provider'));
                    if (tagName === 'video' && this.get('poster')) {
                        attr.poster = this.get('poster');
                    }

                    return attr;
                }
            },
            view: {
                onRender() {
                    this.em.removeSelected();
                    this.em.addSelected(this.el);

                    return this;
                }
            }
        });

        const video = BlockManager.get('video');
        const content = video.get('content');

        delete content.style;
    }
});

export default VideoTypeBuilder;
