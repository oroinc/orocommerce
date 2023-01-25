import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import mediator from 'oroui/js/mediator';
import formToAjaxOptions from 'oroui/js/tools/form-to-ajax-options';

const QuickAddImportFormView = BaseView.extend({
    /**
     * @type {string}
     */
    droppableContainer: '#container',

    events: {
        'change input:file': 'onFileChange',
        'submit': 'onSubmit',
        'dragenter': 'onDragenter',
        'dragover': 'onDragover',
        'dragleave': 'onDragleave',
        'drop': 'onDrop'
    },

    /**
     * @inheritdoc
     */
    constructor: function QuickAddImportFormView(options) {
        QuickAddImportFormView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        if (!options.productsCollection) {
            throw new Error('Option `productsCollection` is require for QuickAddCopyPasteFormComponent');
        }

        const {droppableContainer, productsCollection} = options;
        Object.assign(this, {droppableContainer, productsCollection});

        QuickAddImportFormView.__super__.initialize.call(this, options);

        if (this.$('input:file').val() !== '') {
            // import file is already chosen
            this.onFileChange();
        }
    },

    /**
     * @inheritdoc
     */
    delegateEvents() {
        QuickAddImportFormView.__super__.delegateEvents.call(this);

        if (this.droppableContainer) {
            const eventsEntries = Object.entries({
                dragenter: 'onDragenter',
                dragover: 'onDragover',
                dragleave: 'onDragleave',
                drop: 'onDrop'
            }).map(([event, method]) => [`${event}${this.eventNamespace()}`, this[method].bind(this)]);

            this.$el.closest(this.droppableContainer).on(Object.fromEntries(eventsEntries));
        }

        return this;
    },

    /**
     * @inheritdoc
     */
    undelegateEvents() {
        QuickAddImportFormView.__super__.undelegateEvents.call(this);
        if (this.$el && this.droppableContainer) {
            this.$el.closest(this.droppableContainer).off(this.eventNamespace());
        }
        return this;
    },

    onFileChange() {
        this.$el.submit();
    },

    onSubmit(event) {
        if (event.isDefaultPrevented() || !this.find('input:file')[0].value) {
            // in case form is invalid and submit has been already prevented
            return false;
        }

        event.preventDefault();
        this.submitForm()
            .always(() => this.resetFileInput());
    },

    submitForm(options) {
        const ajaxOptions = formToAjaxOptions(this.$el, {
            ...options,
            success: response => {
                if (response.messages) {
                    Object.entries(response.messages).forEach(([type, messages]) => {
                        messages.forEach(message => mediator.execute('showMessage', type, message));
                    });
                }
                if (response.collection) {
                    const {errors = [], items} = response.collection;
                    errors.forEach(error => mediator.execute('showMessage', 'error', error.message));

                    if (items && items.length && !this.disposed) {
                        const _items = items.map(item => {
                            // omit index attr, since it is not an index of a model in collection
                            const {index, ...attrs} = item;
                            return attrs;
                        });
                        this.productsCollection.addQuickAddRows(_items, {ignoreIncorrectUnit: false});
                    }
                }
            }
        });

        return $.ajax(ajaxOptions);
    },

    resetFileInput() {
        this.find('input:file')[0].value = '';
    },

    onDragenter(event) {
        event.preventDefault();
        event.stopPropagation();
        this.highlight();
    },

    onDragover(event) {
        event.preventDefault();
        event.stopPropagation();
        this.highlight();
    },

    onDragleave(event) {
        event.preventDefault();
        event.stopPropagation();
        this.unhighlight();
    },

    onDrop(event) {
        event.preventDefault();
        event.stopPropagation();
        this.unhighlight();
        const {dataTransfer} = event.originalEvent;

        if (dataTransfer && dataTransfer.files.length) {
            const _dataTransfer = new DataTransfer();
            // upload supported only for one file, so it is only first file is taken
            _dataTransfer.items.add(dataTransfer.files[0]);
            this.find('input:file')[0].files = _dataTransfer.files;

            this.$el.submit();
        }
    },

    highlight() {
        this.$el.addClass('highlight');
    },

    unhighlight() {
        this.$el.removeClass('highlight');
    }
});

export default QuickAddImportFormView;
