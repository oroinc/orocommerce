<?php

namespace Oro\Bundle\WebCatalogBundle\Api;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\FrontendBundle\Api\ResourceApiUrlResolverInterface;
use Oro\Bundle\FrontendBundle\Api\ResourceTypeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Api\Model\SystemPage;
use Oro\Bundle\WebCatalogBundle\Api\Repository\SystemPageRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Resolves the resource type and the URL of the system page REST API resource.
 */
class SystemPageResourceResolver implements ResourceTypeResolverInterface, ResourceApiUrlResolverInterface
{
    private SystemPageRepository $systemPageRepository;
    private UrlGeneratorInterface $urlGenerator;
    private RestRoutesRegistry $routesRegistry;
    private ValueNormalizer $valueNormalizer;

    public function __construct(
        SystemPageRepository $systemPageRepository,
        UrlGeneratorInterface $urlGenerator,
        RestRoutesRegistry $routesRegistry,
        ValueNormalizer $valueNormalizer
    ) {
        $this->systemPageRepository = $systemPageRepository;
        $this->urlGenerator = $urlGenerator;
        $this->routesRegistry = $routesRegistry;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveType(string $routeName, array $routeParameters, RequestType $requestType): ?string
    {
        if (!$this->isSystemPage($routeName, $routeParameters)) {
            return null;
        }

        return 'system_page';
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
        if ('system_page' !== $resourceType || !$this->isSystemPage($routeName, $routeParameters)) {
            return null;
        }

        $entityType = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            SystemPage::class,
            $requestType
        );

        return $this->urlGenerator->generate(
            $this->routesRegistry->getRoutes($requestType)->getItemRouteName(),
            ['entity' => $entityType, 'id' => $routeName]
        );
    }

    private function isSystemPage(string $routeName, array $routeParameters): bool
    {
        return !$routeParameters && null !== $this->systemPageRepository->findSystemPage($routeName);
    }
}
