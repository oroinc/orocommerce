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
    public const TYPE = 'product_collection';
    public const PRODUCT_COLLECTION_ROUTE_NAME = 'oro_product_frontend_product_index';
    public const CONTENT_VARIANT_ID_KEY = 'contentVariantId';
    public const OVERRIDE_VARIANT_CONFIGURATION_KEY = 'overrideVariantConfiguration';

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return self::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle(): string
    {
        return 'oro.product.content_variant.product_collection.label';
    }

    /**
     * {@inheritDoc}
     */
    public function getFormType(): string
    {
        return ProductCollectionVariantType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowed(): bool
    {
        return $this->authorizationChecker->isGranted('oro_product_view');
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteData(ContentVariantInterface $contentVariant): RouteData
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
     * {@inheritDoc}
     */
    public function getApiResourceClassName(): string
    {
        return ProductCollection::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getApiResourceIdentifierDqlExpression($alias)
    {
        return $alias . '.id';
    }

    /**
     * {@inheritDoc}
     */
    public function getAttachedEntity(ContentVariantInterface $contentVariant)
    {
        return $contentVariant->getProductCollectionSegment();
    }
}
