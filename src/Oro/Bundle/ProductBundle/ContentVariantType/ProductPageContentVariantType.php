<?php

namespace Oro\Bundle\ProductBundle\ContentVariantType;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType;
use Oro\Component\Routing\RouteData;
use Oro\Component\WebCatalog\ContentVariantEntityProviderInterface;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The content variant type for a product.
 */
class ProductPageContentVariantType implements ContentVariantTypeInterface, ContentVariantEntityProviderInterface
{
    public const TYPE = 'product_page';

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

    #[\Override]
    public function getName()
    {
        return self::TYPE;
    }

    #[\Override]
    public function getTitle()
    {
        return 'oro.product.content_variant.product_page.label';
    }

    #[\Override]
    public function getFormType()
    {
        return ProductPageVariantType::class;
    }

    #[\Override]
    public function isAllowed()
    {
        return $this->authorizationChecker->isGranted('oro_product_view');
    }

    #[\Override]
    public function getRouteData(ContentVariantInterface $contentVariant)
    {
        /** @var Product $product */
        $product = $this->getAttachedEntity($contentVariant);

        return new RouteData('oro_product_frontend_product_view', ['id' => $product->getId()]);
    }

    #[\Override]
    public function getApiResourceClassName()
    {
        return Product::class;
    }

    #[\Override]
    public function getApiResourceIdentifierDqlExpression($alias)
    {
        return sprintf('IDENTITY(%s.product_page_product)', $alias);
    }

    #[\Override]
    public function getAttachedEntity(ContentVariantInterface $contentVariant)
    {
        return $this->propertyAccessor->getValue($contentVariant, 'productPageProduct');
    }
}
