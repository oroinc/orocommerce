<?php

namespace OroB2B\Bundle\WarehouseBundle\Form\Extension;

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
            'orob2b_product.export_inventory_status_only' => 'orob2b.warehouse.export.inventory_status_only',
            'orob2b_warehouse.detailed_inventory_levels' => 'orob2b.warehouse.export.detailed_inventory_levels',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ExportType::NAME;
    }
}
