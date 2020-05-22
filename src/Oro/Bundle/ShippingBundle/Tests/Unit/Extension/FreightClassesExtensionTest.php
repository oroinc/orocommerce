<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Extension;

use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;
use Oro\Bundle\ShippingBundle\Extension\FreightClassesExtension;

class FreightClassesExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var FreightClassesExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->extension = new FreightClassesExtension();
    }

    /**
     * @param FreightClass $inputData
     * @param bool $expectedData
     *
     * @dataProvider isApplicableProvider
     */
    public function testIsApplicable(FreightClass $inputData, $expectedData)
    {
        /* @var $options ProductShippingOptionsInterface|\PHPUnit\Framework\MockObject\MockObject */
        $options = $this->createMock('Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface');

        $this->assertEquals($expectedData, $this->extension->isApplicable($inputData, $options));
    }

    /**
     * @return array
     */
    public function isApplicableProvider()
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
