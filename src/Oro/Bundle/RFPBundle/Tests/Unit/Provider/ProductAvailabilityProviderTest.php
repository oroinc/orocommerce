<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Provider\ProductAvailabilityProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductAvailabilityProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @return array
     */
    public function isProductApplicableForRFPDataProvider()
    {
        return [
            'simple product' => [
                'type' => Product::TYPE_SIMPLE,
                'expected' => true,
            ],
            'configurable product' => [
                'type' => Product::TYPE_CONFIGURABLE,
                'expected' => false,
            ],
        ];
    }

    /**
     * @param string $type
     * @param bool $expected
     * @dataProvider isProductApplicableForRFPDataProvider
     */
    public function testIsProductApplicableForRFP($type, $expected)
    {
        $provider = new ProductAvailabilityProvider();
        $product = $this->getEntity(Product::class, ['id' => 1, 'type' => $type]);

        $this->assertEquals($expected, $provider->isProductApplicableForRFP($product));
    }
}
