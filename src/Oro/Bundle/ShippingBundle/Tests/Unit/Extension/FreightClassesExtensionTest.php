<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Extension;

use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;
use Oro\Bundle\ShippingBundle\Extension\FreightClassesExtension;

class FreightClassesExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var FreightClassesExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new FreightClassesExtension();
    }

    /**
     * @dataProvider isApplicableProvider
     */
    public function testIsApplicable(FreightClass $inputData, bool $expectedData)
    {
        $options = $this->createMock(ProductShippingOptionsInterface::class);

        $this->assertEquals($expectedData, $this->extension->isApplicable($inputData, $options));
    }

    public function isApplicableProvider(): array
    {
        return [
            'not applicable' => [
                'input' => (new FreightClass)->setCode('test_class'),
                'expected' => false,
            ],
            'applicable' => [
                'input' => (new FreightClass)->setCode('parcel'),
                'expected' => true,
            ],
        ];
    }
}
