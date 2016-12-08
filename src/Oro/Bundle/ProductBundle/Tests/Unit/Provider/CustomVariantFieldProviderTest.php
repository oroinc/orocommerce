<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Provider\CustomVariantFieldProvider;

class CustomVariantFieldProviderTest extends CustomFieldProviderTest
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->provider = new CustomVariantFieldProvider($this->extendConfigProvider, $this->entityConfigProvider);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityCustomFieldsDataProvider()
    {
        return [
            'variant_fields_only' => [
                'fields' => [
                    'size_string' => [
                        'owner' => 'Custom',
                        'label' => 'Size Label',
                        'type' => 'string',
                        'state' => 'Active',
                    ],
                    'color_string' => [
                        'owner' => 'Custom',
                        'label' => 'Color Label',
                        'type' => 'string',
                        'state' => 'Requires update',
                    ],
                    'size_select' => [
                        'owner' => 'Custom',
                        'label' => 'Size Label',
                        'type' => 'enum',
                        'state' => 'Active',
                    ],
                    'color_select' => [
                        'owner' => 'Custom',
                        'label' => 'Color Label',
                        'type' => 'enum',
                        'state' => 'Requires update',
                    ],
                    'slim_fit' => [
                        'owner' => 'Custom',
                        'label' => 'Slim Fit Label',
                        'type' => 'boolean',
                        'state' => 'Active',
                    ],
                    'category' => [
                        'owner' => 'Custom',
                        'label' => 'Category Label',
                        'type' => 'manyToOne',
                        'state' => 'Active',
                    ],
                ],

                'expectedResult' => [
                    'size_select' => ['name' => 'size_select', 'label' => 'Size Label', 'type' => 'enum'],
                    'color_select' => ['name' => 'color_select', 'label' => 'Color Label', 'type' => 'enum'],
                    'slim_fit' => ['name' => 'slim_fit', 'label' => 'Slim Fit Label', 'type' => 'boolean'],
                ],
            ]
        ];
    }
}
