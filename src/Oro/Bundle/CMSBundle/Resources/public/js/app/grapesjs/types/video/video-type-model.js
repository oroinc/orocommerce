export default (BaseTypeModel, {editor}) => {
    const VideoURLRegExp = /^.*((youtu.be\/|vimeo.com\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?)|(live\/))\??v?=?([^#\&\?]*).*/;

    const VideoTypeModel = BaseTypeModel.extend({
        editor,

        constructor: function VideoTypeModel(...args) {
            return VideoTypeModel.__super__.constructor.apply(this, args);
        },

        init() {
            this.listenTo(this, 'change:source', this.updateSrc);
        },

        extractVideoID(url) {
            const match = url.match(VideoURLRegExp);

            if (match && match[8]) {
                return match[8];
            }

            return url;
        },

        getSourceVideoSrc() {
            return this.get('source') || '';
        },

        updateSrc() {
            const prov = this.get('provider');
            let src = '';

            if (prov !== 'so' && this.changed.videoId) {
                this.set('videoId', this.extractVideoID(this.get('videoId')));
            }

            switch (prov) {
                case 'yt':
                    src = this.getYoutubeSrc();
                    break;
                case 'ytnc':
                    src = this.getYoutubeNoCookieSrc();
                    break;
                case 'vi':
                    src = this.getVimeoSrc();
                    break;
                case 'so':
                    src = this.getSourceVideoSrc();
                    break;
            }

            this.set({
                src
            });

            this.setAttributes({
                src
            });
        },

        getProviderTrait() {
            const providerTrait = VideoTypeModel.__super__.getProviderTrait.call(this);
            const options = providerTrait.options
                .filter(prov => this.editor.ComponentRestriction.isAllow(this.getTagNameByProvider(prov.value)))
                .filter(prov => {
                    // Skip source provider
                    if (prov.value === 'so') {
                        return true;
                    }

                    const providerUrl = this.getUrlByProvider(prov.value);

                    if (providerUrl === null) {
                        return false;
                    }

                    return this.editor.ComponentRestriction.isAllowedDomain(providerUrl);
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
            const sourceTrait = VideoTypeModel.__super__.getSourceTraits.call(this);
            // keep poster's trait value in properties,
            // and to save values into attributes only common for all sources, e.g. src, id
            sourceTrait.find(source => source.name === 'poster').changeProp = 1;
            sourceTrait.find(source => source.name === 'src').name = 'source';
            return sourceTrait;
        },

        getAttrToHTML() {
            const attr = VideoTypeModel.__super__.getAttrToHTML.call(this);
            const tagName = this.getTagNameByProvider(this.get('provider'));
            attr.loop && delete attr.loop;
            attr.autoplay && delete attr.autoplay;
            attr.controls && delete attr.controls;

            if (tagName === 'video') {
                if (this.get('poster')) {
                    attr.poster = this.get('poster');
                }

                if (this.get('loop')) {
                    attr.loop = 'loop';
                }

                if (this.get('autoplay')) {
                    attr.autoplay = 'autoplay';
                }

                if (this.get('controls')) {
                    attr.controls = 'controls';
                }
            }

            if (tagName === 'iframe') {
                if (this.get('poster')) {
                    delete attr.poster;
                }

                attr.allowfullscreen = 'allowfullscreen';
            }

            return attr;
        },

        getYoutubeSrc() {
            let url = VideoTypeModel.__super__.getYoutubeSrc.call(this);
            url += this.get('autoplay') ? '&mute=1' : '';
            return url;
        },

        getVimeoSrc() {
            let url = VideoTypeModel.__super__.getVimeoSrc.call(this);
            url += this.get('autoplay') ? '&muted=1' : '';
            return url;
        }
    });

    Object.defineProperty(VideoTypeModel.prototype, 'defaults', {
        value: {
            ...VideoTypeModel.prototype.defaults,
            src: '',
            fallback: '',
            attributes: {
                src: '',
                controls: true
            }
        }
    });

    return VideoTypeModel;
};
