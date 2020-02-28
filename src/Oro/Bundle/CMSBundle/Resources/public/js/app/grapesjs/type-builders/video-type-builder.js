import _ from 'underscore';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const VideoTypeBuilder = BaseTypeBuilder.extend({
    constructor: function VideoTypeBuilder(options) {
        VideoTypeBuilder.__super__.constructor.call(this, options);
    },

    initialize(options) {
        Object.assign(this, _.pick(options, 'editor', 'componentType'));
    },

    execute() {
        const componentRestriction = this.editor.ComponentRestriction;
        this.editor.DomComponents.addType(this.componentType, {
            model: {
                getProviderTrait() {
                    const providerTrait = this.constructor.__super__.getProviderTrait.call(this);
                    const options = providerTrait.options
                        .filter(prov => componentRestriction.isAllow(this.getTagNameByProvider(prov.value)));
                    return {
                        ...providerTrait,
                        options
                    };
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
            }
        });
    }
});

export default VideoTypeBuilder;
