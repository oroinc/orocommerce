import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import TypeModel from './video-type-model';
import TypeView from './video-type-view';

const Index = BaseType.extend({
    parentType: 'video',

    button: {
        attributes: {
            'class': 'fa fa-youtube-play'
        },
        category: 'Basic',
        defaultStyle: {
            height: '400px',
            width: '100%'
        },
        order: 55
    },

    TypeModel,

    TypeView,

    constructor: function VideoTypeBuilder(options) {
        VideoTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        let result = '';
        const isYtProv = /youtube\.com\/embed/.test(el.src);
        const isYtncProv = /youtube-nocookie\.com\/embed/.test(el.src);
        const isViProv = /player\.vimeo\.com\/video/.test(el.src);
        const isExtProv = isYtProv || isYtncProv || isViProv;

        if (el.tagName === 'VIDEO' || (el.tagName === 'IFRAME' && isExtProv)) {
            result = {
                type: 'video',
                initial: true
            };

            if (el.src) {
                result.src = el.getAttribute('src');

                if (el.tagName === 'VIDEO') {
                    result.source = el.getAttribute('src');
                }
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
    }
}, {
    type: 'video'
});

export default Index;
