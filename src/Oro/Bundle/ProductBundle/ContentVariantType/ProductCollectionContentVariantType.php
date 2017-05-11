<?php

namespace Oro\Bundle\ProductBundle\ContentVariantType;

use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionVariantType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Routing\RouteData;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

class ProductCollectionContentVariantType implements ContentVariantTypeInterface
{
    const TYPE = 'product_collection';
    const PRODUCT_COLLECTION_ROUTE_NAME = 'oro_product_frontend_product_index';
    const CONTENT_VARIANT_ID_KEY = 'contentVariantId';

    /**
     * @var SecurityFacade
     */
    private $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
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
        return 'oro.product.content_variant.product_collection.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return ProductCollectionVariantType::NAME;
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
        return new RouteData(
            self::PRODUCT_COLLECTION_ROUTE_NAME,
            [
                self::CONTENT_VARIANT_ID_KEY => $contentVariant->getId(),
            ]
        );
    }
}
