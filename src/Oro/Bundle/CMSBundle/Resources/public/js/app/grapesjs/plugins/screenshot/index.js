import GrapesJS from 'grapesjs';
import html2canvas from 'html2canvas';
import {uniqueId} from 'underscore';
import mediator from 'oroui/js/mediator';
import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import * as icons from './placeholder-icons';

export default GrapesJS.plugins.add('template-screenshot-plugin', (editor, {
    editorView,
    previewFieldName,
    width = 1100,
    height = 450
}) => {
    const {Canvas} = editor;
    const previewField = document.querySelector(`[name="${previewFieldName}"]`);

    const createScreenshot = () => new Promise(async (resolve, reject) => {
        try {
            const canvasIframe = Canvas.getFrameEl();
            const wrapper = canvasIframe.contentDocument.querySelector('[data-gjs-type="wrapper"]');
            canvasIframe.width = width;
            canvasIframe.height = height;
            let maxHeight = height;

            wrapper.style.minHeight = 'auto';
            if (wrapper.clientHeight < maxHeight) {
                maxHeight = wrapper.clientHeight;
            }
            wrapper.style.minHeight = '';

            const canvas = await html2canvas(canvasIframe.contentDocument.body, {
                width,
                height: maxHeight,
                windowWidth: width,
                windowHeight: maxHeight,
                useCORS: true,
                onclone(doc, body) {
                    body.querySelectorAll('iframe, canvas').forEach(node => {
                        let icon = 'main';

                        if (node.parentNode.getAttribute('data-gjs-type') === 'video') {
                            icon = 'play';
                        }

                        if (node.src && node.src.includes('maps.google')) {
                            icon = 'map';
                        }

                        const placeholder = $('<div />', {
                            'css': {
                                width: node.clientWidth,
                                height: node.clientHeight
                            },
                            'class': 'content-placeholder',
                            'html': icons[icon]
                        });

                        if (node.parentNode.getAttribute('poster')) {
                            placeholder.css({
                                'background-image': `url(${node.parentNode.getAttribute('poster')})`,
                                'background-repeat': 'no-repeat',
                                'background-position': 'center center',
                                'background-size': 'cover',
                                'color': 'white'
                            });
                        }

                        node.replaceWith(placeholder[0]);
                    });
                }
            });

            canvasIframe.width = '';
            canvasIframe.height = '';

            canvas.toBlob(blob => {
                const imageFile = new File([blob], `${uniqueId('preview-')}.png`, {
                    type: 'image/png'
                });
                const container = new DataTransfer();
                container.items.add(imageFile);
                previewField.files = container.files;
                previewField.dispatchEvent(new Event('change'));

                editorView.form.find('[type="submit"]').prop('disabled', false);

                resolve(imageFile);
            }, 'image/png');
        } catch (e) {
            reject(e);
        }
    });

    editorView.listenTo(mediator, 'before:submitPage', queue => {
        mediator.execute('showLoading');
        const promise = createScreenshot();
        promise.catch(() => {
            mediator.execute('hideLoading');
            mediator.execute('showFlashMessage', 'error', __('oro.cms.wysiwyg.screenshot_plugin.onerror'));
        });
        queue.push(promise);
    });
});
