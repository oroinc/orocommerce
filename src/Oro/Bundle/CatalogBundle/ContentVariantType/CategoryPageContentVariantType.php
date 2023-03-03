<?php

namespace Oro\Bundle\CatalogBundle\ContentVariantType;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Component\Routing\RouteData;
use Oro\Component\WebCatalog\ContentVariantEntityProviderInterface;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The content variant type for a master catalog category.
 */
class CategoryPageContentVariantType implements ContentVariantTypeInterface, ContentVariantEntityProviderInterface
{
    public const TYPE = 'category_page';
    public const CATEGORY_CONTENT_VARIANT_ID_KEY = 'categoryContentVariantId';
    public const OVERRIDE_VARIANT_CONFIGURATION_KEY = 'overrideVariantConfiguration';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->propertyAccessor = $propertyAccessor;
    }

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
        return 'oro.catalog.category.entity_label';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return CategoryPageVariantType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed()
    {
        return $this->authorizationChecker->isGranted('oro_catalog_category_view');
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteData(ContentVariantInterface $contentVariant)
    {
        /** @var Category $category */
        $category = $this->getAttachedEntity($contentVariant);

        return new RouteData(
            'oro_product_frontend_product_index',
            [
                self::CATEGORY_CONTENT_VARIANT_ID_KEY => $contentVariant->getId(),
                RequestProductHandler::CATEGORY_ID_KEY => $category->getId(),
                RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY =>
                    !$this->propertyAccessor->getValue($contentVariant, 'excludeSubcategories'),
                self::OVERRIDE_VARIANT_CONFIGURATION_KEY => $contentVariant->isOverrideVariantConfiguration(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getApiResourceClassName()
    {
        return Category::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiResourceIdentifierDqlExpression($alias)
    {
        return sprintf('IDENTITY(%s.category_page_category)', $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttachedEntity(ContentVariantInterface $contentVariant)
    {
        return $this->propertyAccessor->getValue($contentVariant, 'categoryPageCategory');
    }
}
