<?php

namespace Oro\Bundle\CatalogBundle\Api;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\FrontendBundle\Api\ResourceApiUrlResolverInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Resolves the the URL of the category products JSON:API REST API resource.
 */
class CategoryProductsRestJsonApiResourceApiUrlResolver implements ResourceApiUrlResolverInterface
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
        if (!\array_key_exists('categoryId', $routeParameters)) {
            return null;
        }

        $entityType = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            Product::class,
            $requestType
        );

        $filterName = ($routeParameters['includeSubcategories'] ?? false)
            ? 'filter[rootCategory][gte]'
            : 'filter[category]';

        return $this->urlGenerator->generate(
            $this->routesRegistry->getRoutes($requestType)->getListRouteName(),
            ['entity' => $entityType, $filterName => $routeParameters['categoryId']]
        );
    }
}
