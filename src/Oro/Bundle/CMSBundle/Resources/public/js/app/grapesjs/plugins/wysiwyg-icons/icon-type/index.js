import BaseType, {CATEGORIES} from 'orocms/js/app/grapesjs/types/base-type';
import IconTypeView from './icon-type-view';
import IconTypeModel from './icon-type-model';
import iconIdTraitInit from '../traits/icon-id-trait';
import IconsCollectionView from '../settings/icons-collection';
import IconsService from '../icons-service';

const ICON_WIDGET_REGEXP = /\{\{\s?widget_icon\(\"([\w\S]+)\"\s?\,?\s?(\{[\s\S]+?\})?\)\s?\}\}/gm;

const extractAttributes = attrStr => {
    try {
        return JSON.parse(attrStr);
    } catch (e) {
        return {};
    }
};

const matchAndReplaceIconExp = content => {
    return content
        .replace(new RegExp(`data-gjs-type="${IconType.type}"`, 'gm'), '')
        .replace(ICON_WIDGET_REGEXP, (matched, iconId, attrs) => {
            const attrsStr = Object.entries(extractAttributes(attrs))
                .reduce((str, [name, value]) => {
                    str += ` ${name}="${value}"`;
                    return str;
                }, '');

            return `<span data-init-icon="${iconId}" ${attrsStr}></span>`;
        });
};

const IconType = BaseType.extend({
    button: {
        label: 'Icon',
        category: {
            label: CATEGORIES.uiComponents,
            order: 5
        },
        activate: true,
        attributes: {
            'class': 'fa fa-star'
        },
        order: 5
    },

    iconsData: {},

    TypeModel: IconTypeModel,

    TypeView: IconTypeView,

    constructor: function IconType(...args) {
        IconType.__super__.constructor.apply(this, args);
    },

    editorEvents: {
        load: 'onChangeTheme',
        changeTheme: 'onChangeTheme'
    },

    onInit() {
        iconIdTraitInit({editor: this.editor});
    },

    getTypeModelOptions() {
        const editor = this.editor;

        return {
            ...IconType.__super__.getTypeModelOptions.call(this),
            settings: [{
                name: 'iconId',
                getView(options) {
                    return new IconsCollectionView({
                        ...options,
                        editor
                    });
                }
            }]
        };
    },

    beforeParse(content) {
        return matchAndReplaceIconExp(content);
    },

    isComponent(el) {
        if (el.nodeType === Element.ELEMENT_NODE && el.getAttribute('data-init-icon')) {
            return {
                type: IconType.type,
                tagName: 'svg',
                iconId: el.getAttribute('data-init-icon')
            };
        }
    },

    onChangeTheme() {
        const iconsService = new IconsService({});
        const {parentView} = this.editor;

        if (iconsService.isSvgIconsSupport(parentView.getCurrentTheme())) {
            this.execute();
        } else {
            const {Blocks} = this.editor;
            Blocks.remove(this.componentType);
            Blocks.render();
        }
    }
}, {
    type: 'icon'
});

export default IconType;
