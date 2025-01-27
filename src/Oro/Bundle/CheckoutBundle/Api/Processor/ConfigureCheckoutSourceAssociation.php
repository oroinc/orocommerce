<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Configures "source" association for Checkout entity.
 */
class ConfigureCheckoutSourceAssociation implements ProcessorInterface
{
    private const string ASSOCIATION_NAME = 'source';

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $associationDefinition = $context->getResult()->getField(self::ASSOCIATION_NAME);
        $dependsOn = $associationDefinition->getDependsOn();
        $targetAssociationPrefix = self::ASSOCIATION_NAME . ConfigUtil::PATH_DELIMITER;
        $targetAssociationMappings = $this->doctrineHelper->getEntityMetadataForClass(CheckoutSource::class)
            ->getAssociationMappings();
        foreach ($targetAssociationMappings as $targetAssociationName => $targetAssociationMapping) {
            if (($targetAssociationMapping['type'] & ClassMetadata::TO_ONE)
                && is_a($targetAssociationMapping['targetEntity'], CheckoutSourceEntityInterface::class, true)
            ) {
                $dependsOn[] = $targetAssociationPrefix . $targetAssociationName;
            }
        }
        $associationDefinition->setDependsOn($dependsOn);
    }
}
