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
                }
            }
        });
    }
});

export default VideoTypeBuilder;
