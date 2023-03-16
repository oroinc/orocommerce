import GrapesJS from 'grapesjs';
import grapesjsExportPlugin from 'grapesjs-plugin-export';

const filenameGenerator = ({editorView, entityLabels}) => {
    const filename = [];
    const pageTitleContainer = document.querySelector('.page-title__entity-title');

    if (entityLabels) {
        const {plural_label: pluralLabel} = entityLabels;
        filename.push(pluralLabel.toLowerCase().replace(/\s/g, '-'));
    }

    if (editorView.inFallbackContainer) {
        const tabId = editorView.$parent.parent().attr('id');
        const tab = document.querySelector(`[aria-controls="${tabId}"]`);

        filename.push(tab.innerText.toLowerCase().replace(/[\s\(\)]+/g, '-'));
    }

    if (pageTitleContainer) {
        filename.push(pageTitleContainer.innerText.toLowerCase().replace(/\s/g, '-'));
    }

    filename.push(Date.now());

    const filenameRes = filename.join('_')
        .replace(/[,.]/g, '')
        .replace(/[\-]{2,}/g, '-')
        .replace(/[?><|/\\.,\(\)^%$#@!\[\]]/g, '');

    return `${filenameRes}.zip`;
};

export default GrapesJS.plugins.add('grapesjs-export', (editor, options = {}) => {
    return grapesjsExportPlugin(editor, {
        filename: filenameGenerator.bind(editor, options),
        ...options
    });
});
