<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Adds acceptable entity classes to "source" association of Checkout entity.
 */
class AddAcceptableEntityClassesToCheckoutSourceAssociation implements ProcessorInterface
{
    private const string ASSOCIATION_NAME = 'source';

    public function __construct(
        private readonly ResourcesProvider $resourcesProvider,
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var MetadataContext $context */

        $entityMetadata = $context->getResult();
        if (null === $entityMetadata) {
            return;
        }

        $associationMetadata = $entityMetadata->getAssociation(self::ASSOCIATION_NAME);
        if (null === $associationMetadata) {
            return;
        }

        $associationConfig = $context->getConfig()->getField(self::ASSOCIATION_NAME);
        if (null !== $associationConfig) {
            $associationMetadata->setAcceptableTargetClassNames(
                $this->getAcceptableEntities($associationConfig, $context->getVersion(), $context->getRequestType())
            );
        }
        $associationMetadata->setEmptyAcceptableTargetsAllowed(false);
    }

    private function getAcceptableEntities(
        EntityDefinitionFieldConfig $associationConfig,
        string $version,
        RequestType $requestType
    ): array {
        $acceptableEntities = [];
        $associationPrefix = self::ASSOCIATION_NAME . ConfigUtil::PATH_DELIMITER;
        $associationPrefixLength = \strlen($associationPrefix);
        $targetEntityMetadata = $this->doctrineHelper->getEntityMetadataForClass(CheckoutSource::class);
        $dependsOn = $associationConfig->getDependsOn();
        foreach ($dependsOn as $targetFieldPath) {
            if (!str_starts_with($targetFieldPath, $associationPrefix)) {
                continue;
            }
            $targetFieldName = substr($targetFieldPath, $associationPrefixLength);
            if (!$targetEntityMetadata->hasAssociation($targetFieldName)) {
                continue;
            }

            $targetClass = $targetEntityMetadata->getAssociationTargetClass($targetFieldName);
            if (is_a($targetClass, CheckoutSourceEntityInterface::class, true)
                && $this->resourcesProvider->isResourceAccessible($targetClass, $version, $requestType)
            ) {
                $acceptableEntities[] = $targetClass;
            }
        }

        return $acceptableEntities;
    }
}
