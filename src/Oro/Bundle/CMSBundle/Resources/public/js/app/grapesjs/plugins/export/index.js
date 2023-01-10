import GrapesJS from 'grapesjs';
import grapesjsExportPlugin from 'grapesjs-plugin-export';

export default GrapesJS.plugins.add('grapesjs-export', (editor, options = {}) => {
    return grapesjsExportPlugin(editor, options);
});
