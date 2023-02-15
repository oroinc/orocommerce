<?php

namespace Oro\Bundle\ProductBundle\Api;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\FrontendBundle\Api\ResourceApiUrlResolverInterface;
use Oro\Bundle\ProductBundle\Api\Model\ProductCollection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Resolves the the URL of the product collection REST API resource.
 */
class ProductCollectionRestResourceApiUrlResolver implements ResourceApiUrlResolverInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private RestRoutesRegistry $routesRegistry;
    private ValueNormalizer $valueNormalizer;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        RestRoutesRegistry $routesRegistry,
        ValueNormalizer $valueNormalizer
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->routesRegistry = $routesRegistry;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveApiUrl(
        string $routeName,
        array $routeParameters,
        string $resourceType,
        RequestType $requestType
    ): ?string {
        if (!\array_key_exists('contentVariantId', $routeParameters)) {
            return null;
        }

        $entityType = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            ProductCollection::class,
            $requestType
        );

        return $this->urlGenerator->generate(
            $this->routesRegistry->getRoutes($requestType)->getItemRouteName(),
            ['entity' => $entityType, 'id' => $routeParameters['contentVariantId']]
        );
    }
}
