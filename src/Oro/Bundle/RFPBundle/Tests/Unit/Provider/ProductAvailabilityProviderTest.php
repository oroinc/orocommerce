<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Provider\ProductAvailabilityProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductAvailabilityProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @dataProvider isProductApplicableForRFPDataProvider
     */
    public function testIsProductApplicableForRFP(string $type, bool $expected)
    {
        $provider = new ProductAvailabilityProvider();
        $product = $this->getEntity(Product::class, ['id' => 1, 'type' => $type]);

        $this->assertEquals($expected, $provider->isProductApplicableForRFP($product));
    }

    public function isProductApplicableForRFPDataProvider(): array
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
}
