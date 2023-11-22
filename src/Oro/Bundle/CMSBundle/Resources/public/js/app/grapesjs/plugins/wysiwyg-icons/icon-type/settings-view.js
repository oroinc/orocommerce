import BaseView from 'oroui/js/app/views/base/view';
import IconsCollectionView from './settings/icons-collection';

const IconSettingsView = BaseView.extend({
    autoRender: true,

    icons: null,

    constructor: function IconSettingsView(...args) {
        IconSettingsView.__super__.constructor.apply(this, args);
    },

    async initialize(options) {
        this.getAndParseIconCollection = options.getAndParseIconCollection;
        IconSettingsView.__super__.initialize.call(this, options);
    },

    render() {
        this._deferredRender();

        IconSettingsView.__super__.render.call(this);

        this.getAndParseIconCollection().then(data => {
            this.subview('icons', new IconsCollectionView({
                container: this.el,
                data
            }));

            this._resolveDeferredRender();
        });
    }
});

export default IconSettingsView;
