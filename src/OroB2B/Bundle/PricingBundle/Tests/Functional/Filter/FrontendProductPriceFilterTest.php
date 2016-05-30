<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Filter;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Filter\FrontendProductPriceFilter;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

/**
 * @dbIsolation
 */
class FrontendProductPriceFilterTest extends WebTestCase
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var FrontendProductPriceFilter
     */
    protected $filter;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
            ]
        );
        $this->registry = $this->getContainer()->get('doctrine');
        $cpl = $this->getReference('1f');
        /** @var PriceListRequestHandler|\PHPUnit_Framework_MockObject_MockObject $handler */
        $handler = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $handler->expects($this->once())->method('getPriceListByAccount')->willReturn($cpl);
        $this->filter = new FrontendProductPriceFilter(
            $this->getContainer()->get('form.factory'),
            $this->getContainer()->get('oro_filter.filter_utility'),
            $this->getContainer()->get('orob2b_product.formatter.product_unit_label'),
            $handler
        );
        $this->filter->init(
            'minimum_price',
            [
                'type' => 'frontend-product-price',
                'data_name' => 'USD',
                'enabled' => true,
                'translatable' => true,
                'label' => 'Price (USD)'
            ]
        );
        $this->filter->setRegistry($this->registry);
        $this->filter->setProductPriceClass('OroB2BPricingBundle:CombinedProductPrice');
    }

    /**
     * @dataProvider filterDataProvider
     * @param array $data
     * @param int $productsCount
     */
    public function testFilter(array $data, $productsCount)
    {
        $qb = $this->registry->getRepository('OroB2BProductBundle:Product')->createQueryBuilder('product');
        $adapter = new OrmFilterDatasourceAdapter($qb);

        $this->filter->apply($adapter, $data);

        $products = $adapter->getQueryBuilder()->getQuery()->getResult();
        $this->assertCount($productsCount, $products);
    }

    /**
     * @return array
     */
    public function filterDataProvider()
    {
        return [
            [
                'data' => [
                    'data_name' => 'p',
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => 1,
                    'value_end' => 10,
                    'unit' => 'liter',
                ],
                'count' => 2,
            ],
            [
                'data' => [
                    'data_name' => 'p',
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => 1,
                    'value_end' => 13,
                    'unit' => 'liter',
                ],
                'count' => 3,
            ],
            [
                'data' => [
                    'data_name' => 'p',
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => 10,
                    'value_end' => 12,
                    'unit' => 'liter',
                ],
                'count' => 1,
            ],
        ];
    }
}
