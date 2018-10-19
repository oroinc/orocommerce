<?php

namespace Oro\Bundle\ProductBundle\ContentVariantType;

use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionVariantType;
use Oro\Component\Routing\RouteData;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ProductCollectionContentVariantType implements ContentVariantTypeInterface
{
    const TYPE = 'product_collection';
    const PRODUCT_COLLECTION_ROUTE_NAME = 'oro_product_frontend_product_index';
    const CONTENT_VARIANT_ID_KEY = 'contentVariantId';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
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
        return ProductCollectionVariantType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed()
    {
        return $this->authorizationChecker->isGranted('oro_product_view');
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
