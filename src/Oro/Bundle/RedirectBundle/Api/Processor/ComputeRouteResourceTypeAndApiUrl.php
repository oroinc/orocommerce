<?php

namespace Oro\Bundle\RedirectBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\FrontendBundle\Api\ResourceApiUrlResolverInterface;
use Oro\Bundle\FrontendBundle\Api\ResourceTypeResolverInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "resourceType" and "apiUrl" fields for Route entity.
 */
class ComputeRouteResourceTypeAndApiUrl implements ProcessorInterface
{
    private const RESOURCE_TYPE_FIELD_NAME = 'resourceType';
    private const API_URL_FIELD_NAME = 'apiUrl';
    private const UNKNOWN_RESOURCE_TYPE = 'unknown';

    private ResourceTypeResolverInterface $resourceTypeResolver;
    private ResourceApiUrlResolverInterface $apiUrlResolver;

    public function __construct(
        ResourceTypeResolverInterface $resourceTypeResolver,
        ResourceApiUrlResolverInterface $apiUrlResolver
    ) {
        $this->resourceTypeResolver = $resourceTypeResolver;
        $this->apiUrlResolver = $apiUrlResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        $apiUrlFieldRequested = $context->isFieldRequested(self::API_URL_FIELD_NAME, $data);
        if (!\array_key_exists(self::RESOURCE_TYPE_FIELD_NAME, $data)
            && ($apiUrlFieldRequested || $context->isFieldRequested(self::RESOURCE_TYPE_FIELD_NAME))
        ) {
            $resourceType = $this->resourceTypeResolver->resolveType(
                $context->getResultFieldValue('routeName', $data),
                $context->getResultFieldValue('routeParameters', $data),
                $context->getRequestType()
            );
            if (null === $resourceType) {
                $resourceType = self::UNKNOWN_RESOURCE_TYPE;
            }
            $data[self::RESOURCE_TYPE_FIELD_NAME] = $resourceType;
        }
        if ($apiUrlFieldRequested) {
            $apiUrl = null;
            $resourceType = $data[self::RESOURCE_TYPE_FIELD_NAME] ?? null;
            if (null !== $resourceType) {
                $apiUrl = $this->apiUrlResolver->resolveApiUrl(
                    $context->getResultFieldValue('routeName', $data),
                    $context->getResultFieldValue('routeParameters', $data),
                    $resourceType,
                    $context->getRequestType()
                );
            }
            $data[self::API_URL_FIELD_NAME] = $apiUrl;
        }
        $context->setData($data);
    }
}
