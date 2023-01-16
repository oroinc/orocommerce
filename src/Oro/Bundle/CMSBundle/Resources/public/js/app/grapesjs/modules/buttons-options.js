const buttonsConfig = {
    options: {
        props: {
            comparator: 'order'
        },
        buttons: {
            'sw-visibility': {
                order: 10
            },
            'fullscreen': {
                order: 10
            },
            'undo': {
                order: 30
            },
            'redo': {
                order: 30
            },
            'gjs-open-import-webpage': {
                order: 20,
                className: 'fa fa-code'
            },
            'canvas-clear': {
                order: 50
            },
            'enable-code-mode': {
                order: 40
            }
        }
    },
    views: {
        props: {
            comparator: 'order'
        },
        buttons: {
            'open-sm': {
                order: 10
            },
            'open-layers': {
                order: 20
            },
            'open-blocks': {
                order: 30
            }
        }
    }
};

export const getPanelButtonProps = (panelId, buttonId) => {
    if (!buttonsConfig[panelId]) {
        return {};
    }

    if (!buttonsConfig[panelId].buttons[buttonId]) {
        return {};
    }

    return buttonsConfig[panelId].buttons[buttonId];
};

export const isPanelConfigExist = panelId => {
    return !!buttonsConfig[panelId];
};

export const getPanelProps = panelId => {
    if (!buttonsConfig[panelId] || !buttonsConfig[panelId].props) {
        return {};
    }

    return buttonsConfig[panelId].props;
};

export default buttonsConfig;
