import _ from 'underscore';
import GrapesJS from 'grapesjs';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import traitManagerExtends from 'orocms/js/app/grapesjs/plugins/components/trait-manager-extends';
import {unescapeTwigExpression} from '../../utils';
import fullscreenCommand from '../../commands/fullscreen';
import clearCanvasCommand from '../../commands/clear-canvas';

export default GrapesJS.plugins.add('grapesjs-components', function(editor, options) {
    const {Blocks, Commands, Panels} = editor;

    const superCategoryDefaults = Blocks.Category.prototype.defaults;
    Blocks.Category.prototype.defaults = () => {
        return {
            ...superCategoryDefaults(),
            order: 100
        };
    };

    Blocks.Categories = Blocks.Categories.extend({
        comparator: 'order',

        add(...args) {
            const res = this.__super__.add.apply(this, args);
            this.sort();
            return res;
        }
    });

    editor.getHtml = _.wrap(editor.getHtml, (func, ...args) => unescapeTwigExpression(func.apply(editor, args)));

    editor.editor.runDefault = _.wrap(editor.editor.runDefault, (func, opts = {}) => {
        if (!editor.editor.get('Commands')) {
            return;
        }

        func.call(editor.editor, opts);
    });

    Commands.add('fullscreen', fullscreenCommand);
    Commands.add('core:canvas-clear', clearCanvasCommand);

    traitManagerExtends(editor);

    editor.ComponentRestriction = new ComponentRestriction(editor, options);

    Panels.removeButton('options', 'preview');
    Panels.addButton('options', [
        {
            id: 'undo',
            className: 'fa fa-undo',
            command(editor) {
                editor.runCommand('core:undo');
            }
        },
        {
            id: 'redo',
            className: 'fa fa-repeat',
            command(editor) {
                editor.runCommand('core:redo');
            }
        },
        {
            id: 'canvas-clear',
            className: 'fa fa-trash',
            command(editor) {
                editor.runCommand('core:canvas-clear');
            }
        }
    ]);

    editor.once('destroy', function() {
        editor.componentManager.dispose();
        delete editor.ComponentRestriction;
    });
});
