import _ from 'underscore';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const undoAutoPlay = url => url.replace(/(autoplay=).*?(&)/, '$1' + 0 + '$2');

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

        const video = BlockManager.get('video');
        const content = video.get('content');

        content.style = {
            height: '400px',
            width: '100%'
        };

        this.editor.DomComponents.addType(this.componentType, {
            isComponent(el) {
                let result = '';
                const isYtProv = /youtube\.com\/embed/.test(el.src);
                const isYtncProv = /youtube-nocookie\.com\/embed/.test(el.src);
                const isViProv = /player\.vimeo\.com\/video/.test(el.src);
                const isExtProv = isYtProv || isYtncProv || isViProv;

                if (el.tagName === 'VIDEO' || (el.tagName === 'IFRAME' && isExtProv)) {
                    result = {
                        type: 'video'
                    };

                    if (el.src) {
                        result.src = el.src;
                    }

                    if (isExtProv) {
                        if (isYtProv) {
                            result.provider = 'yt';
                        } else if (isYtncProv) {
                            result.provider = 'ytnc';
                        } else if (isViProv) {
                            result.provider = 'vi';
                        }
                    } else {
                        result = {
                            ...result,
                            controls: el.getAttribute('controls') ? 1 : 0,
                            loop: el.getAttribute('loop') ? 1 : 0,
                            autoplay: el.getAttribute('autoplay') ? 1 : 0,
                            poster: el.getAttribute('poster') || ''
                        };
                    }
                }
                return result;
            },

            model: {
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

                    return attr;
                },

                getYoutubeSrc() {
                    let url = this.constructor.__super__.getYoutubeSrc.call(this);
                    url += this.get('autoplay') ? '&mute=1' : '';
                    return url;
                },

                getVimeoSrc() {
                    let url = this.constructor.__super__.getVimeoSrc.call(this);
                    url += this.get('autoplay') ? '&muted=1' : '';
                    return url;
                }
            },
            view: {
                onRender() {
                    this.em.removeSelected();
                    this.em.addSelected(this.el);
                    return this;
                },

                updateVideo() {
                    this.constructor.__super__.updateVideo.call(this);
                    const prov = this.model.get('provider');

                    if (prov === 'so') {
                        this.videoEl.autoplay = false;
                    }
                },

                updateSrc() {
                    this.constructor.__super__.updateSrc.call(this);
                    const {videoEl} = this;
                    if (!videoEl) {
                        return;
                    }
                    const prov = this.model.get('provider');

                    // Disable autoplay for source
                    if (prov !== 'so') {
                        videoEl.src = undoAutoPlay(videoEl.src);
                    }
                },

                renderByProvider(prov) {
                    const videoEl = this.constructor.__super__.renderByProvider.call(this, prov);

                    videoEl.src = undoAutoPlay(videoEl.src);
                    this.videoEl = videoEl;
                    return videoEl;
                }
            }
        });
    }
});

export default VideoTypeBuilder;
