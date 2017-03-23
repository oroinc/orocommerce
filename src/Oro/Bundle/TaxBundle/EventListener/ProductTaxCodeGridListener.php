<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;

class ProductTaxCodeGridListener extends TaxCodeGridListener
{
    /**
     * @param DatagridConfiguration $config
     */
    protected function addColumn(DatagridConfiguration $config)
    {
        $config->offsetSetByPath(
            sprintf('[columns][%s]', $this->getDataName()),
            [
                'label' => $this->getColumnLabel(),
                'inline_editing' => [
                    'enable' => true,
                    'editor' => [
                        'view' => 'orotax/js/app/views/editor/product-tax-code-editor-view',
                        'view_options' => [
                            'value_field_name' => 'taxCode',
                        ],
                    ],
                    'autocomplete_api_accessor' => [
                        'entity_name' => ProductTaxCode::class,
                        'field_name' => 'code'
                    ],
                    'save_api_accessor' => [
                        'route' => 'oro_api_patch_product_tax_code',
                        'query_parameter_names' => ['id']
                    ]
                ]
            ]
        );
    }
}
