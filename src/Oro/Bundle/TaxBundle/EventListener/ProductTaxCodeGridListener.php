<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

/**
 * Adds taxCode field to the products grid.
 */
class ProductTaxCodeGridListener extends TaxCodeGridListener
{
    #[\Override]
    public function onBuildBefore(BuildBefore $event): void
    {
        parent::onBuildBefore($event);
        if (!$this->isSupported()) {
            return;
        }

        $this->addFieldsAcl($event->getConfig());
    }

    #[\Override]
    protected function addColumn(DatagridConfiguration $config): void
    {
        $config->offsetSetByPath(
            sprintf('[columns][%s]', $this->getDataName()),
            [
                'label' => $this->getColumnLabel(),
                'renderable' => false,
                'inline_editing' => [
                    'enable' => true,
                    'editor' => [
                        'view' => 'orotax/js/app/views/editor/product-tax-code-editor-view',
                        'view_options' => [
                            'value_field_name' => $this->getTaxCodeField()
                        ]
                    ],
                    'autocomplete_api_accessor' => [
                        'class' => 'oroui/js/tools/search-api-accessor',
                        'label_field_name' => 'code',
                        'search_handler_name' => 'oro_product_tax_code'
                    ],
                    'save_api_accessor' => [
                        'route' => 'oro_api_patch_product_tax_code',
                        'query_parameter_names' => ['id']
                    ]
                ]
            ]
        );
    }

    #[\Override]
    protected function addFilter(DatagridConfiguration $config): void
    {
        parent::addFilter($config);

        $config->offsetSetByPath(sprintf('[filters][columns][%s][renderable]', $this->getDataName()), false);
    }

    protected function addFieldsAcl(DatagridConfiguration $config): void
    {
        $config->offsetSetByPath(
            sprintf('[fields_acl][columns][%s]', $this->getDataName()),
            ['data_name' => 'product.taxCode', 'column_name' => 'taxCode']
        );
    }
}
