<?php

namespace Oro\Bundle\CatalogBundle\ContentVariantType;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Component\Routing\RouteData;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Content variant type for category
 */
class CategoryPageContentVariantType implements ContentVariantTypeInterface
{
    const TYPE = 'category_page';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param PropertyAccessor              $propertyAccessor
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        PropertyAccessor $propertyAccessor
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
        $category = $this->propertyAccessor->getValue($contentVariant, 'categoryPageCategory');

        return new RouteData(
            'oro_product_frontend_product_index',
            [
                'categoryId' => $category->getId(),
                'includeSubcategories' => !$this->propertyAccessor->getValue($contentVariant, 'excludeSubcategories'),
                RequestProductHandler::OVERRIDE_VARIANT_CONFIGURATION_KEY =>
                    $contentVariant->isOverrideVariantConfiguration(),
            ]
        );
    }
}
