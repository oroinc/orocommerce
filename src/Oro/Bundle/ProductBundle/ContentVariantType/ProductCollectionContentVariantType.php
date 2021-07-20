<?php

namespace Oro\Bundle\ProductBundle\ContentVariantType;

use Oro\Bundle\ProductBundle\Api\Model\ProductCollection;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionVariantType;
use Oro\Component\Routing\RouteData;
use Oro\Component\WebCatalog\ContentVariantEntityProviderInterface;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The content variant type for a product collection.
 */
class ProductCollectionContentVariantType implements
    ContentVariantTypeInterface,
    ContentVariantEntityProviderInterface
{
    const TYPE = 'product_collection';
    const PRODUCT_COLLECTION_ROUTE_NAME = 'oro_product_frontend_product_index';
    const CONTENT_VARIANT_ID_KEY = 'contentVariantId';
    const OVERRIDE_VARIANT_CONFIGURATION_KEY = 'overrideVariantConfiguration';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

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
                self::OVERRIDE_VARIANT_CONFIGURATION_KEY => $contentVariant->isOverrideVariantConfiguration(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getApiResourceClassName()
    {
        return ProductCollection::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiResourceIdentifierDqlExpression($alias)
    {
        return $alias . '.id';
    }

    /**
     * {@inheritdoc}
     */
    public function getAttachedEntity(ContentVariantInterface $contentVariant)
    {
        return $contentVariant->getProductCollectionSegment();
    }
}
