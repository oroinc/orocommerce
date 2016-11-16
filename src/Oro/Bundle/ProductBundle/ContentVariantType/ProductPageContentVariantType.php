<?php

namespace Oro\Bundle\ProductBundle\ContentVariantType;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\RouteData;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ProductPageContentVariantType implements ContentVariantTypeInterface
{
    const TYPE = 'product_page';

    /**
     * @var SecurityFacade
     */
    private $securityFacade;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param SecurityFacade $securityFacade
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(SecurityFacade $securityFacade, PropertyAccessor $propertyAccessor)
    {
        $this->securityFacade = $securityFacade;
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
        return 'oro.product.content_variant.product_page.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return ProductPageVariantType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed()
    {
        return $this->securityFacade->isGranted('oro_product_view');
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteData(ContentVariantInterface $contentVariant)
    {
        /** @var Product $product */
        $product = $this->propertyAccessor->getValue($contentVariant, 'productPageProduct');

        return new RouteData('oro_product_frontend_product_view', ['id' => $product->getId()]);
    }
}
