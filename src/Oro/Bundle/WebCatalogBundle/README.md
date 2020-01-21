# OroWebCatalogBundle

OroWebCatalogBundle enables OroCommerce management console administrators to set a different structure and content of the storefront for individual customers, customer groups, or all visitors of the website by combining product pages, category pages, system pages, and product collections into custom catalogs.

## Table of Contents

 - [Breadcrumbs](#breadcrumbs)
 - [How to create your own content variant](#how-to-create-your-own-content-variant)

## Breadcrumbs

With OroWebCatalogBundle you can override the default breadcrumbs data source.

After creating and enabling a new WebCatalog on a website, 
the breadcrumbs are rendered in sync with a user-defined WebCatalog tree structure.


**Example:**

As an illustration, let's examine a simple category tree below: 
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
look the following way:
```
WebCatalog <name of the webcatalog> \ Medical Apparel \ Medical Uniforms
```

## How to create your own content variant:

There are 5 content variant types registered out-of-the-box:

- System page
- Landing page 
- Category
- Product page
- Product Collection page

The main entity responsible for content variants is `ContentVariant`.
Entity `ContentVariant` is extendable. If you want to add another entity to a content variant, you should extend it.

To create your own content variant, create a relation between the content variant and your entity.

For example,  to create a `Blog Post` Content Variant, proceed through the steps below.

**1. Create migration** 

You should create migration which adds relation between the content variant entity and your entity.
In our example it is relation between the ContentVariant and BlogPost entities.
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

Add form type for your entity content variant.

This form type is used on the Create Content Node page to add and edit you content variant.
```php
    use Symfony\Component\Form\AbstractType;

    class BlogPostPageVariantType extends AbstractType
    {
        ...
    }
```

**3. Create content variant type**

Next, create a service which should implement `ContentVariantTypeInterface` and be tagged with `oro_web_catalog.content_variant_type` tag.
In this service, provide individual type name, title, created before form the type which is used for create and update content variant.
`getRouteData` method should return route data to render your content variant on frontend application side.
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

        /**
         * {@inheritdoc}
         */
        public function getApiResourceClassName()
        {
            return BlogPost::class;
        }
    
        /**
         * {@inheritdoc}
         */
        public function getApiResourceIdentifierDqlExpression($alias)
        {
            return sprintf('IDENTITY(%s.content_variant_blog_post)', $alias);
        }
    }
```

`ContentVariantTypeContentVariantTypeRegistry` is used to collect all content variant types.
To render `Add Content Variant` dropdown button with all available content variants, `WebCatalogExtension` twig extension is used.

**4. Create Storefront API**

If your content variant is represented by an ORM entity (like the blog post described in this example),
enable the storefront API for it using the `Resources/config/oro/api_frontend.yml` configuration file.
For more details, see [Storefront REST API](https://doc.oroinc.com/backend/api/storefront/).

If your content variant is represented by a non-ORM entity, enabling storefront API may be more time-consuming. As an example you can investigate how it is done for the system page content variant:

- [SystemPage model](./Api/Model/SystemPage.php)
- [SystemPage declaration in Resources/config/oro/api_frontend.yml](./Resources/config/oro/api_frontend.yml)
- [SystemPageRepository class](./Api/Repository/SystemPageRepository.php)
- [LoadSystemPage API processor](./Api/Processor/LoadSystemPage.php)
- [ExpandSystemPageContentVariant API processor](./Api/Processor/ExpandSystemPageContentVariant.php)
- [LoadSystemPageContentVariantSubresource API processor](./Api/Processor/LoadSystemPageContentVariantSubresource.php)

**Adding scope selectors for content variants is automatic**

`PageVariantTypeExtension` form type extension adds scope type with appropriate selectors for each content variant type.
`ContentVariant` can has only one scope, any `Scope` can be applied for different Content Variants.

As a result, you will have possibility to add content node variant for your entity.
And rendering this content variant on store frontend according selected scopes.

### Default content variant
Each content variant of content node may be selected as default using `ContentVariant` `is_default` flag.
It's mean that if Content Node has scopes not assigned to any Content Variant of this node, that scopes will be assigned to content variant marked as default.

### Sitemap
To add the created content variant to Sitemap, create an appropriate provider. Please see OroSeoBundle documentation, section Sitemap.
