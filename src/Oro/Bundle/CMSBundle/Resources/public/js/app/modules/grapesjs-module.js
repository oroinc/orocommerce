import ComponentManager from 'orocms/js/app/grapesjs/plugins/components/component-manager';
import DefaultTypeBuilder from 'orocms/js/app/grapesjs/type-builders/default-type-builder';
import ContentBlockTypeBuilder from 'orocms/js/app/grapesjs/type-builders/content-block-type-builder';
import ContentWidgetTypeBuilder from 'orocms/js/app/grapesjs/type-builders/content-widget-type-builder';
import FileTypeBuilder from 'orocms/js/app/grapesjs/type-builders/file-type-builder';
import ImageTypeBuilder from 'orocms/js/app/grapesjs/type-builders/image-type-builder';
import QuoteTypeBuilder from 'orocms/js/app/grapesjs/type-builders/quote-type-builder';
import TableTypeBuilder from 'orocms/js/app/grapesjs/type-builders/table-type-builder';
import TableResponsiveTypeBuilder from 'orocms/js/app/grapesjs/type-builders/table-responsive-type-builder';
import LinkBLockTypeBuilder from 'orocms/js/app/grapesjs/type-builders/link-block-builder';
import LinkButtonTypeBuilder from 'orocms/js/app/grapesjs/type-builders/link-button-type-builder';
import LinkTypeBuilder from 'orocms/js/app/grapesjs/type-builders/link-type-builder';
import CodeTypeBuilder from 'orocms/js/app/grapesjs/type-builders/code-type-builder';
import TextBasicTypeBuilder from 'orocms/js/app/grapesjs/type-builders/text-basic-type-builder';
import TextTypeBuilder from 'orocms/js/app/grapesjs/type-builders/text-type-builder';
import VideoTypeBuilder from 'orocms/js/app/grapesjs/type-builders/video-type-builder';
import MapTypeBuilder from 'orocms/js/app/grapesjs/type-builders/map-type-builder';
import RadioTypeBuilder from 'orocms/js/app/grapesjs/type-builders/radio-type-builder';
import GridTypeBuilder from 'orocms/js/app/grapesjs/type-builders/grid-type-builder';
import ColumnTypeBuilder from 'orocms/js/app/grapesjs/type-builders/column-type-builder';
import RowTypeBuilder from 'orocms/js/app/grapesjs/type-builders/row-type-builder';

ComponentManager.registerComponentTypes({
    'default': {
        Constructor: DefaultTypeBuilder
    },
    'quote': {
        Constructor: QuoteTypeBuilder
    },
    'table': {
        Constructor: TableTypeBuilder
    },
    'table-responsive': {
        Constructor: TableResponsiveTypeBuilder
    },
    'content-block': {
        Constructor: ContentBlockTypeBuilder,
        optionNames: ['excludeContentBlockAlias']
    },
    'content-widget': {
        Constructor: ContentWidgetTypeBuilder,
        optionNames: ['excludeContentWidgetAlias']
    },
    'code': {
        Constructor: CodeTypeBuilder
    },
    'link': {
        Constructor: LinkTypeBuilder
    },
    'link-block': {
        Constructor: LinkBLockTypeBuilder
    },
    'link-button': {
        Constructor: LinkButtonTypeBuilder
    },
    'text': {
        Constructor: TextTypeBuilder
    },
    'text-basic': {
        Constructor: TextBasicTypeBuilder
    },
    'file': {
        Constructor: FileTypeBuilder
    },
    'image': {
        Constructor: ImageTypeBuilder
    },
    'radio': {
        Constructor: RadioTypeBuilder
    },
    'video': {
        Constructor: VideoTypeBuilder
    },
    'map': {
        Constructor: MapTypeBuilder
    },
    'row': {
        Constructor: RowTypeBuilder
    },
    'column': {
        Constructor: ColumnTypeBuilder
    },
    'grid': {
        Constructor: GridTypeBuilder
    }
});
