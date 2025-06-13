<?php

namespace Oro\Bundle\OrderBundle\ImportExport\Converter;

use Oro\Bundle\ImportExportBundle\Converter\ComplexData\Mapping\ComplexDataMappingProviderInterface;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;

/**
 * Loads data mapping for "status" field of order entity
 * when "Enable External Status Management" configuration option is enabled.
 */
class OrderStatusMappingProvider implements ComplexDataMappingProviderInterface
{
    public function __construct(
        private readonly OrderConfigurationProviderInterface $configurationProvider
    ) {
    }

    #[\Override]
    public function getMapping(array $mapping): array
    {
        if ($this->configurationProvider->isExternalStatusManagementEnabled()) {
            $mapping['order']['fields']['status'] = [
                'target_path' => 'relationships.status.data',
                'ref' => 'order_status'
            ];
            $mapping['order_status'] = [
                'target_type' => 'orderstatuses',
                'entity' => 'Extend\Entity\EV_Order_Status',
                'lookup_field' => 'name',
                'ignore_not_found' => true
            ];
        }

        return $mapping;
    }
}
