<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;
use Oro\Bundle\ProductBundle\EventListener\BuildProductListSchemaOrgListener;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class BuildProductListSchemaOrgListenerTest extends \PHPUnit\Framework\TestCase
{
    private BuildProductListSchemaOrgListener $listener;

    protected function setUp(): void
    {
        $this->listener = new BuildProductListSchemaOrgListener();
    }

    public function testOnBuildQueryProductList(): void
    {
        $searchQuery = $this->createMock(SearchQueryInterface::class);
        $searchQuery
            ->expects(self::once())
            ->method('addSelect')
            ->withConsecutive([
                [
                    'schema_org_description_LOCALIZATION_ID as schema_org_description',
                    'schema_org_brand_name_LOCALIZATION_ID as schema_org_brand_name'
                ],
            ])
            ->willReturnSelf();

        $event = new BuildQueryProductListEvent('related_products', $searchQuery);
        $this->listener->onBuildQueryProductList($event);
    }

    /**
     * @dataProvider resultProductListProvider
     */
    public function testOnBuildResultProductList(array $productData, array $productViews, array $expected): void
    {
        $productId = current($productData)['id'];
        $event = new BuildResultProductListEvent('related_products', $productData, $productViews);

        $this->listener->onBuildResultProductList($event);

        self::assertEquals($event->getProductView($productId)->schemaOrgDescription, $expected['schemaOrgDescription']);
        self::assertEquals($event->getProductView($productId)->schemaOrgBrandName, $expected['schemaOrgBrandName']);
    }

    private function resultProductListProvider(): array
    {
        $productData = [
            1 => [
                'id'                   => 1,
                'type'                 => Product::TYPE_SIMPLE,
                'sku'                  => 'p1',
                'name'                 => 'product 1',
                'image'                => '',
                'unit'                 => 'items',
                'product_units'        => '',
                'newArrival'           => 0,
                'variant_fields_count' => ''
            ],
            2 => [
                'id'                   => 2,
                'type'                 => Product::TYPE_SIMPLE,
                'sku'                  => 'p2',
                'name'                 => 'product 2',
                'image'                => '',
                'unit'                 => 'items',
                'product_units'        => '',
                'newArrival'           => 0,
                'variant_fields_count' => ''
            ]
        ];

        $productViews = [];

        foreach ($productData as $id => $product) {
            $view = new ProductView();
            foreach ($product as $field => $value) {
                $view->{$field} = $value;
            }
            $productViews[$id] = $view;
            $productData[$id]['schema_org_description'] = 'test_schema_org_description_' . $id;
            $productData[$id]['schema_org_brand_name'] = 'test_schema_org_brand_name_' . $id;
        }

        return [
            'first' => [
                'productData' => [1 => $productData[1]],
                'productViews' => [1 => $productViews[1]],
                'expected' => [
                    'schemaOrgDescription' => 'test_schema_org_description_1',
                    'schemaOrgBrandName' => 'test_schema_org_brand_name_1'
                ]
            ],
            'second' => [
                'productData' => [2 => $productData[2]],
                'productViews' => [2 => $productViews[2]],
                'expected' => [
                    'schemaOrgDescription' => 'test_schema_org_description_2',
                    'schemaOrgBrandName' => 'test_schema_org_brand_name_2'
                ]
            ]
        ];
    }
}
