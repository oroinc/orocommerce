import BaseCollection from 'oroui/js/app/models/base/collection';
import IconModel from './model';
import {SyncMachine} from 'chaplin';

const IconsCollection = BaseCollection.extend({
    ...SyncMachine,

    model: IconModel,

    constructor: function IconsCollection(...args) {
        IconsCollection.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.listenTo(this, 'change:selected', this.onSelectedInCollection.bind(this));
        IconsCollection.__super__.initialize.call(this, options);
    },

    onSelectedInCollection(model, selected) {
        selected && this.each(item => item.get('selected') && item.id !== model.id && item.set('selected', false));
    },

    getSelected() {
        return this.find(model => model.get('selected'));
    },

    setSelected(id) {
        if (this.isSyncing() || this.isUnsynced()) {
            return this.listenToOnce(this, 'synced', () => {
                this.setSelected(id);
            });
        }

        if (!this.get(id)) {
            return;
        }

        return this.add([{
            id,
            selected: true
        }], {
            merge: true
        });
    }
});

export default IconsCollection;
