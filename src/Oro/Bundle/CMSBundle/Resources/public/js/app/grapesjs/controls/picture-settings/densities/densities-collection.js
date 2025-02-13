import BaseCollection from 'oroui/js/app/models/base/collection';
import DensitiesModel from './densities-model';

const DensitiesCollection = BaseCollection.extend({
    model: DensitiesModel,

    maxDensity: 4,

    mimeType: null,

    comparator: 'density',

    constructor: function DensitiesCollection(...args) {
        DensitiesCollection.__super__.constructor.apply(this, args);
    },

    initialize(data, options) {
        if (options.mimeType !== void 0) {
            this.mimeType = options.mimeType;
        }

        DensitiesCollection.__super__.initialize.call(this, data, options);
    },

    getAvailableOptions() {
        const densities = [];
        const added = this.map(model => model.get('density'));

        for (let i = 1; i <= this.maxDensity; i++) {
            if (!added.includes(i)) {
                densities.push({
                    id: i,
                    name: `${i}x`
                });
            }
        }

        return densities;
    },

    getDensityForNew() {
        return this.getAvailableOptions().length ? this.getAvailableOptions()[0].id : null;
    },

    removeItem(model) {
        return this.remove([model]);
    },

    toSrcSet() {
        if (this.length === 1 && this.where({origin: true})) {
            return '';
        }

        return this.reduce((srcset, model) => {
            const src = model.toSrcSet();
            if (src) {
                srcset.push(model.toSrcSet());
            }
            return srcset;
        }, []).join(', ');
    },

    getOrigin() {
        return this.findWhere({origin: true});
    },

    addEmptyItem() {
        if (!this.find(model => !model.get('origin') && !model.get('url'))) {
            this.add({
                density: this.getDensityForNew()
            });
        }
    },

    isEmpty() {
        return !this.filter(model => !model.get('origin')).length;
    }
}, {
    DENSITY_REGEXP: /([\S]+)\s?([\d]x)?/,
    DENSITY_STRICT_REGEXP: /([\S]+)\s([\d]x)/,

    parseSrcSet(srcset) {
        if (!DensitiesCollection.isContainDensities(srcset)) {
            return [{
                origin: true,
                url: srcset
            }];
        }

        return srcset.split(', ').map(item => {
            const [, src, density = '1x'] = item.match(DensitiesCollection.DENSITY_REGEXP);

            return {
                origin: parseInt(density) === 1,
                url: src,
                density: parseInt(density)
            };
        });
    },
    isContainDensities(srcset) {
        DensitiesCollection.DENSITY_STRICT_REGEXP.lastIndex = 0;

        return DensitiesCollection.DENSITY_STRICT_REGEXP.test(srcset);
    },
    avoidDensity(srcset) {
        return srcset ? srcset.split(' ')[0] : srcset;
    }
});

export default DensitiesCollection;
