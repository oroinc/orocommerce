import $ from 'jquery';
import _ from 'underscore';
import grapesjs from 'grapesjs';

const addScrollingHintsToContainer = ($containerEl, editor) => {
    const EVENT_NAMESPACE = _.uniqueId('.panel-scrolling-hints');

    const $topHelperEL = $('<div></div>', {
        'class': 'scroll-hint-top',
        'aria-hidden': true,
        'data-helper': ''
    });
    const $bottomHelperEL = $('<div></div>', {
        'class': 'scroll-hint-bottom',
        'aria-hidden': true,
        'data-helper': ''
    });

    const isEndOfScroll = el => el.scrollHeight - el.scrollTop === el.clientHeight;
    const refreshScrollHelpers = el => {
        // Check if element has scroll
        if ((el.scrollHeight - el.clientHeight) > 0) {
            $topHelperEL.toggleClass('hide', el.scrollTop <= 0);
            $topHelperEL.css('top', el.scrollTop);
            $bottomHelperEL.toggleClass('hide', isEndOfScroll(el));
            $bottomHelperEL.css('top', el.scrollTop + el.clientHeight);
        } else {
            $topHelperEL.addClass('hide');
            $bottomHelperEL.addClass('hide');
        }

        el.style[`padding-${_.isRTL() ? 'left' : 'right'}`] = `${el.offsetWidth - el.scrollWidth}px`;
    };

    $containerEl
        .prepend($topHelperEL)
        .append($bottomHelperEL)
        .on(`scroll${EVENT_NAMESPACE}`, e => refreshScrollHelpers(e.target));

    $(window).on(`resize${EVENT_NAMESPACE}`, () => refreshScrollHelpers($containerEl[0]));

    refreshScrollHelpers($containerEl[0]);

    const mutationObserver = new MutationObserver(_.debounce(mutations => {
        for (const mutation of mutations ) {
            // Prevent recursive mutation
            if (!mutation.target.hasAttribute('data-helper')) {
                refreshScrollHelpers($containerEl[0]);
                break;
            }
        }
    }, 50));

    mutationObserver.observe($containerEl[0], {
        attributes: true,
        childList: true,
        subtree: true,
        characterData: false
    });

    editor.once('destroy', () => {
        $topHelperEL.remove();
        $bottomHelperEL.remove();
        $containerEl.off(EVENT_NAMESPACE);
        $(window).off(EVENT_NAMESPACE);
        mutationObserver.disconnect();
    });
};

export default grapesjs.plugins.add('grapesjs-panel-scrolling-hints', editor => {
    editor.Commands.add(
        'add-scrolling-hints-to-container',
        (editor, sender, {$container}) => addScrollingHintsToContainer($container, editor)
    );

    editor.on('load', () => {
        addScrollingHintsToContainer(editor.Panels.getPanel('views-container').view.$el, editor);
    });
});
