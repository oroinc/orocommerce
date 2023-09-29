import StatefulModel from 'oroui/js/app/models/base/stateful-model';

const RteStateModel = StatefulModel.extend({
    defaults: {
        content: null,
        range: null,
        useOuterHTML: false
    },

    observedAttributes: ['content'],

    constructor: function RteStateModel(...args) {
        RteStateModel.__super__.constructor.apply(this, args);
    },

    initialize(options = {}) {
        const {componentModel} = options;
        if (componentModel) {
            this.listenTo(componentModel.em, 'destroy', this.destroy.bind(this));

            componentModel.set('stateModel', this);
        }

        RteStateModel.__super__.initialize.call(this, options);
    },

    execute(action) {
        switch (action) {
            case RteStateModel.UNDO:
                this.undo();
                break;
            case RteStateModel.REDO:
                this.redo();
                break;
        }
    },

    undo() {
        if (this.history.setIndex(this.history.get('index') - 1)) {
            return this.updateCurrentState();
        }

        return false;
    },

    redo() {
        if (this.history.setIndex(this.history.get('index') + 1)) {
            return this.updateCurrentState();
        }

        return false;
    },

    updateCurrentState() {
        const state = this.history.getCurrentState();
        this.setState(state.get('data'));

        return state;
    },

    getState() {
        return {
            content: this.get('content'),
            range: this.get('range')
        };
    },

    setState({content, range}) {
        if (content) {
            this.set({
                range,
                content
            }, {
                silent: true
            });
        }
    }
}, {
    UNDO: 'undo',
    REDO: 'redo'
});

export default RteStateModel;
