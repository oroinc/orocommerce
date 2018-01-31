Oro\Bundle\WebCatalogBundle\OroWebCatalogBundle
===============================================

Table of Contents
-----------------
 - [Description](#description)
 - [Breadcrumbs](#breadcrumbs)
 - [How to create own content variant](#how-to-create-own-content-variant)

Description
------------

The OroWebCatalogBundle introduces ability to manage multiple WebCatalogs from the UI.


Breadcrumbs
------------

With OroWebCatalogBundle you can override the default breadcrumbs data source.

After creating and enabling new WebCatalog in a website, 
the breadcrumbs will be rendered in sync with the user-defined WebCatalog tree structure.


**Example:**

Let's have an simple category tree like below: 
```
- WebCatalog <name of the webcatalog>
   - Lighting Products
        - Architectural Floodlighting
        - Headlamps
   - Medical Apparel
        - Medical Uniforms
   - Office Furniture
   - Retail Supplies
        - POS Systems
        - Printers
```
When navigating to ```Medical Uniforms```, the breadcrumbs
will like look following:
```
WebCatalog <name of the webcatalog> \ Medical Apparel \ Medical Uniforms
```

How to create own content variant:
---------------------------------

There are 5 content variant types registered out of the box:
- System page
- Landing page 
- Category
- Product page
- Product Collection page

Main entity that is responsible for content variants is `ContentVariant` entity.
Entity `ContentVariant` is extended entity. If you want to add another entity to content variant you should extend it.

For create your own content variant you should create relation between content variant and your entity.

For example if you want to create `Blog Post` Content Variant:

**1. Create migration** 

You should create migration which add relation between content variant entity and your entity.
In our example it will be relation between ContentVariant and BlogPost entities.
This migration should implement `ExtendExtensionAwareInterface`.

```php

    class OroWebCatalogBundle implements Migration, ExtendExtensionAwareInterface
    {
        /**
         * {@inheritdoc}
         */
        public function up(Schema $schema, QueryBag $queries)
        {
            $this->createRelationToBlogPostFromContentVariant($schema);
            ...
        }
    
        /**
         * @param Schema $schema
         */
        private function createRelationToBlogPostFromContentVariant(Schema $schema)
        {
            if ($schema->hasTable('oro_web_catalog_variant')) {
                $table = $schema->getTable('oro_web_catalog_variant');
                $this->extendExtension->addManyToOneRelation(
                    $schema,
                    $table,
                    'content_variant_blog_post', // Relation field name
                    'blog_post', // Your entity table name
                    'id',
                    [
                        'entity' => ['label' => 'blog_post.entity_label'], // Your entity label translation key
                        'extend' => [
                            'is_extend' => true,
                            'owner' => ExtendScope::OWNER_CUSTOM,
                            'cascade' => ['persist', 'remove'],
                            'on_delete' => 'CASCADE',
                        ],
                        'datagrid' => ['is_visible' => false],
                        'form' => ['is_enabled' => false],
                        'view' => ['is_displayable' => false],
                        'merge' => ['display' => false],
                    ]
                );
            }
        }
        ...
    }
```

**2. Add form type**

Also you should add form type for your entity content variant
This form type will be used on create content node page for adding and editing you content variant.
```php
    use Symfony\Component\Form\AbstractType;

    class BlogPostPageVariantType extends AbstractType
    {
        ...
    }
```

**3. Create content variant type**

After that, you should create service which should implement `ContentVariantTypeInterface` and tagged with `oro_web_catalog.content_variant_type` tag.
In this service you should provide individual type name, title, created before form type which will be used for create and update content variant.
`getRouteData` method should return route data for rendering you content variant on frontend application side.
In our case it may be `new RouteData('frontend_blog_post_view', ['id' => $post->getId()]);`

```php
    use Oro\Component\WebCatalog\ContentVariantTypeInterface;

    class ProductPageContentVariantType implements ContentVariantTypeInterface
    {
        const TYPE = 'blog_post_page';
        
        ...
        
        /**
         * {@inheritdoc}
         */
        public function getName()
        {
            return self::TYPE;
        }
        
        /**
         * {@inheritdoc}
         */
        public function getTitle()
        {
            return 'blog_post_page.label';
        }
        
        /**
         * {@inheritdoc}
         */
        public function getFormType()
        {
            return BlogPostPageVariantType::class;
        }
        
        /**
         * {@inheritdoc}
         */
        public function getRouteData(ContentVariantInterface $contentVariant)
        {
            /** @var BlogPost $post */
            $post = $this->propertyAccessor->getValue($contentVariant, 'contentVariantBlogPost');
    
            return new RouteData('frontend_blog_post_view', ['id' => $post->getId()]);
        }
    }
```

There is `ContentVariantTypeContentVariantTypeRegistry` for collect all content variant types.
For render `Add Content Variant` dropdown button with all available content variants is used `WebCatalogExtension` twig extension .

**Adding scope selectors for content variants is automatic**

`PageVariantTypeExtension` form type extension adds scope type with appropriate selectors for each content variant type.
`ContentVariant` can has only one scope, any `Scope` can be applied for different Content Variants.

As a result, you will have possibility to add content node variant for your entity.
And rendering this content variant on store frontend according selected scopes.

### Default content variant
Each content variant of content node may be selected as default using `ContentVariant` `is_default` flag.
It's mean that if Content Node has scopes not assigned to any Content Variant of this node, that scopes will be assigned to content variant marked as default.

### Sitemap
For adding created content variant to Sitemap you should create appropriate provider. Please see OroSeoBundle documentation, section Sitemap.
