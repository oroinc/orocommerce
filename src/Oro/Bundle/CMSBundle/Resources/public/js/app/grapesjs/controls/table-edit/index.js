import {groupBy} from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import actions from './actions';
import template from 'tpl-loader!orocms/templates/controls/table-edit-template.html';
import TableModify from './table-modify';

const eventsUp = 'change:canvasOffset frame:scroll component:update';

const TableEditView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['table', 'selected']),

    className: 'gjs-rte-toolbar gjs-one-bg table-edition-toolbar',

    autoRender: true,

    template,

    table: null,

    selected: null,

    events: {
        'click [data-command]': 'onClick'
    },

    constructor: function TableEditView(...args) {
        TableEditView.__super__.constructor.apply(this, args);
    },

    getTemplateData() {
        const data = TableEditView.__super__.getTemplateData.call(this);

        data['actionGroups'] = Object.values(groupBy(
            actions.filter(({context}) => {
                if (!context) {
                    return true;
                }

                return Array.isArray(context)
                    ? context.includes(this.selected.get('type'))
                    : this.selected.get('type') === context;
            }), 'group'));

        return data;
    },

    initialize(options) {
        TableEditView.__super__.initialize.call(this, options);
        this.tableModify = new TableModify(this.table);
    },

    render() {
        TableEditView.__super__.render.call(this);
        const editorModel = this.table.em;
        this.$el.css('visibility', 'hidden');
        setTimeout(() => this.updatePosition(), 0);

        editorModel.off(eventsUp, this.updatePosition, this);
        editorModel.on(eventsUp, this.updatePosition, this);

        this.$el.tooltip({
            selector: '[data-toggle="tooltip"]'
        });

        editorModel.get('Canvas').canvasView.toolsWrapper.classList.add('float-editor-enabled');
    },

    updatePosition() {
        if (!this.table) {
            return;
        }

        const {width} = this.table.em.get('Canvas').getRect();
        const pos = this.table.em.get('Canvas').getTargetToElementFixed(this.selected.view.el, this.el);
        const selectedWidth = this.selected.view.el.clientWidth;

        this.$el.css({
            'top': pos.top,
            'pointer-events': 'all',
            'visibility': ''
        });

        if (selectedWidth < this.el.clientWidth &&
            ((width < pos.canvasOffsetLeft + this.el.clientWidth) || (pos.canvasOffsetLeft < this.el.clientWidth / 2))
        ) {
            this.$el.css({
                left: pos.left,
                transform: 'none'
            });
        }
    },

    onClick(event) {
        const command = event.currentTarget.dataset.command;

        this.tableModify.modify(command, {
            selected: this.selected
        });
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        const editorModel = this.table.em;

        this.table.em.off(eventsUp, this.updatePosition, this);
        this.$el.tooltip('dispose');
        this.$el.css('pointer-events', '');

        editorModel.get('Canvas').canvasView.toolsWrapper.classList.remove('float-editor-enabled');

        TableEditView.__super__.dispose.call(this);
    }
});

export default TableEditView;
