<?php

namespace Oro\Bundle\WarehouseBundle\Form\Extension;

use Oro\Bundle\ImportExportBundle\Form\Type\ExportType;

class InventoryLevelExportTypeExtension extends InventoryLevelExportTemplateTypeExtension
{
    const NAME = 'oro_importexport_export_type_extension';

    /**
     * @return array
     */
    public static function getProcessorAliases()
    {
        return [
            'oro_product.inventory_status_only' => 'oro.product.export.inventory_status_only',
            'oro_warehouse.detailed_inventory_levels' => 'oro.warehouse.export.detailed_inventory_levels',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ExportType::NAME;
    }
}
