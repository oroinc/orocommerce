<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Provider\ProductWebsiteSearchTypeProvider;

class ProductWebsiteSearchTypeProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductWebsiteSearchTypeProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->provider = new ProductWebsiteSearchTypeProvider();
    }

    public function testLabel(): void
    {
        $this->assertEquals(
            'oro.product.frontend.website_search_type.label',
            $this->provider->getLabel()
        );
    }

    /**
     * @param string $routeParam
     * @param string $expect
     *
     * @dataProvider getRouteProvider
     */
    public function testGetRoute(string $routeParam, string $expect): void
    {
        $this->assertEquals(
            $expect,
            $this->provider->getRoute($routeParam)
        );
    }

    /**
     * @return array
     */
    public function getRouteProvider(): array
    {
        return [
            'empty param'     => [
                'routeParam' => '',
                'expect'     => 'oro_product_frontend_product_index',
            ],
            'not empty param' => [
                'routeParam' => 'search string',
                'expect'     => 'oro_product_frontend_product_index',
            ],
        ];
    }

    /**
     * @param string $param
     * @param array  $expect
     *
     * @dataProvider getRouteParametersDataProvider
     */
    public function testGetRouteParameters(string $param, array $expect): void
    {
        $this->assertEquals(
            $expect,
            $this->provider->getRouteParameters($param)
        );
    }

    /**
     * @return array
     */
    public function getRouteParametersDataProvider(): array
    {
        return [
            'empty param' => [
                'routeParam' => '',
                'expect'     => [],
            ],
            'with param'  => [
                'routeParam' => 'search string',
                'expect'     => [
                    'grid'   => [
                        'frontend-product-search-grid' =>
                            'f%5Ball_text%5D%5Bvalue%5D=search+string&f%5Ball_text%5D%5Btype%5D=1',
                    ],
                    'search' => 'search string',
                    'searchType' => 'product'
                ],
            ],
        ];
    }
}
