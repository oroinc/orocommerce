import {debounce} from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import ApiAccessor from 'oroui/js/tools/api-accessor';
import 'jquery-ui/widgets/sortable';
import PictureSettingsCollectionView from './picture-settings-collection-view';

const PictureTypeSettingsView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['editor', 'dialog']),

    className: 'drag-n-drop-sorting-view__wrapper row-oro picture-type-settings',

    autoRender: true,

    editor: null,

    dialog: null,

    validateApiProps: {
        http_method: 'POST',
        route: 'oro_cms_wysiwyg_content_validate'
    },

    constructor: function PictureTypeSettingsView(...args) {
        this.validate = debounce(this.validate.bind(this), 300);
        PictureTypeSettingsView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        const {props} = options;

        this.subview('sourceCollection', new PictureSettingsCollectionView({
            ...props,
            ...options
        }));

        this.subview('sourceCollection').collection.on('validate', this.validate.bind(this));

        this.validateApiAccessor = new ApiAccessor(this.validateApiProps);

        PictureTypeSettingsView.__super__.initialize.apply(this, options);
    },

    render() {
        PictureTypeSettingsView.__super__.render.call(this);

        this.$el.append(this.subview('sourceCollection').$el);
    },

    getData() {
        return this.subview('sourceCollection').getData();
    },

    validate() {
        this.dialog.blockSaveButton(true);
        this.validateApiAccessor.send({}, {
            content: this.getTempContent(),
            className: this.editor.parentView.entityClass,
            fieldName: this.editor.parentView.$el.attr('data-grapesjs-field')
        }).then(({success, errors}) => {
            this.subview('sourceCollection').collection.each((model, index) => {
                const error = errors.find(({line}) => line === index + 1);
                if (error) {
                    model.set({
                        invalid: true,
                        errorMessage: error.messageRaw
                    });
                } else {
                    model.set({
                        invalid: false,
                        errorMessage: ''
                    });
                }
            });

            this.dialog.blockSaveButton(!success);
        });
    },

    getTempContent() {
        return this.subview('sourceCollection').toHTML();
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.$('[data-toggle="popover"]').popover('dispose');
        PictureTypeSettingsView.__super__.dispose.call(this);
    }
});

export default PictureTypeSettingsView;
