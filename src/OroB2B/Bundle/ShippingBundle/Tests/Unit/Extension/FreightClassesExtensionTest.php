<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Extension;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;
use OroB2B\Bundle\ShippingBundle\Extension\FreightClassesExtension;

class FreightClassesExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var FreightClassesExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
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
        /* @var $options ProductShippingOptionsInterface|\PHPUnit_Framework_MockObject_MockObject */
        $options = $this->getMock('OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface');

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
