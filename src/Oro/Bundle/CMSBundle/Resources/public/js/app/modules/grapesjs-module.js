import ComponentManager from 'orocms/js/app/grapesjs/plugins/components/component-manager';
import DefaultTypeBuilder from 'orocms/js/app/grapesjs/type-builders/default-type-builder';
import ContentBlockTypeBuilder from 'orocms/js/app/grapesjs/type-builders/content-block-type-builder';
import ContentWidgetTypeBuilder from 'orocms/js/app/grapesjs/type-builders/content-widget-type-builder';
import FileTypeBuilder from 'orocms/js/app/grapesjs/type-builders/file-type-builder';
import ImageTypeBuilder from 'orocms/js/app/grapesjs/type-builders/image-type-builder';
import TableTypeBuilder from 'orocms/js/app/grapesjs/type-builders/table-type-builder';
import TableResponsiveTypeBuilder from 'orocms/js/app/grapesjs/type-builders/table-responsive-type-builder';
import LinkButtonTypeBuilder from 'orocms/js/app/grapesjs/type-builders/link-button-type-builder';
import CodeTypeBuilder from 'orocms/js/app/grapesjs/type-builders/code-type-builder';
import TextTypeBuilder from 'orocms/js/app/grapesjs/type-builders/text-type-builder';

ComponentManager.registerComponentTypes({
    'default': {
        Constructor: DefaultTypeBuilder
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
    'link-button': {
        Constructor: LinkButtonTypeBuilder
    },
    'text': {
        Constructor: TextTypeBuilder
    },
    'file': {
        Constructor: FileTypeBuilder
    },
    'image': {
        Constructor: ImageTypeBuilder
    }
});
