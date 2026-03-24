<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Shared\Rest\SetLocationHeader;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Sets the location of the newly created Checkout entity to the "Location" response header
 * when this Checkout entity is created based on the current parent entity.
 */
class SetLocationHeaderForStartCheckoutFromAnotherEntity implements ProcessorInterface
{
    private RestRoutesRegistry $routesRegistry;
    private UrlGeneratorInterface $urlGenerator;
    private ValueNormalizer $valueNormalizer;
    private EntityIdTransformerRegistry $entityIdTransformerRegistry;

    public function __construct(
        RestRoutesRegistry $routesRegistry,
        UrlGeneratorInterface $urlGenerator,
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerRegistry $entityIdTransformerRegistry
    ) {
        $this->routesRegistry = $routesRegistry;
        $this->urlGenerator = $urlGenerator;
        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        if ($context->getResponseHeaders()->has(SetLocationHeader::RESPONSE_HEADER_NAME)) {
            // the Location header is already set
            return;
        }

        if (!$context->hasResult()) {
            return;
        }

        $result = $context->getResult();
        if (!\is_array($result)) {
            return;
        }

        if ($context->isExisting()) {
            return;
        }

        $metadata = $context->getMetadata();
        if (null === $metadata) {
            return;
        }

        $requestType = $context->getRequestType();
        $entityType = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $context->getClassName(),
            $requestType
        );
        $entityId = $this->getEntityIdTransformer($requestType)->transform(
            $result[$metadata->getIdentifierFieldNames()[0]],
            $metadata
        );
        $location = $this->urlGenerator->generate(
            $this->routesRegistry->getRoutes($requestType)->getItemRouteName(),
            ['entity' => $entityType, 'id' => $entityId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $context->getResponseHeaders()->set(SetLocationHeader::RESPONSE_HEADER_NAME, $location);
    }

    private function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }
}
