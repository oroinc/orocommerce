<?php

namespace Oro\Bundle\CatalogBundle\EventListener\ORM;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\ORM\MetadataBuilderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * This builder listens to the load of Product entity metadata and
 * disable cascade detach operation for the `category` field that is relation to the Category entity
 */
class ProductMetadataBuilder implements MetadataBuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function supports(ConfigInterface $extendConfig)
    {
        $entityConfigId = $extendConfig->getId();

        return $entityConfigId instanceof EntityConfigId && is_a($entityConfigId->getClassName(), Product::class, true);
    }

    /**
     * {@inheritDoc}
     */
    public function build(ClassMetadataBuilder $metadataBuilder, ConfigInterface $extendConfig)
    {
        //TODO: should be removed after BAP-16101
        $metadata = $metadataBuilder->getClassMetadata();
        if (!$metadata->hasAssociation('category')) {
            return;
        }

        $metadata->associationMappings['category']['cascade'] = array_diff(
            $metadata->associationMappings['category']['cascade'],
            ['detach']
        );
        $metadata->associationMappings['category']['isCascadeDetach'] = false;
    }
}
