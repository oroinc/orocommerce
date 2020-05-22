<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Normalizer\ProductPriceNormalizer;
use PHPUnit\Framework\TestCase;

class ProductPriceNormalizerTest extends TestCase
{
    /**
     * @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldHelper;

    /**
     * @var ProductPriceNormalizer
     */
    private $normalizer;

    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        $this->normalizer = new ProductPriceNormalizer($this->fieldHelper);
    }

    public function testSupportsNormalization()
    {
        static::assertTrue($this->normalizer->supportsNormalization(new ProductPrice()));
        static::assertFalse($this->normalizer->supportsNormalization(null));
    }

    public function testNormalize()
    {
        $this->fieldHelper
            ->expects(static::any())
            ->method('getFields')
            ->willReturn([
                ['name' => 'column1'],
                ['name' => 'column2'],
                ['name' => 'priceList'],
                ['name' => 'column3'],
            ]);

        $expected = [
            'column1' => null,
            'column2' => null,
            'column3' => null,
        ];

        static::assertSame(
            $expected,
            $this->normalizer->normalize(new ProductPrice())
        );
    }
}
