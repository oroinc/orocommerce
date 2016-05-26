<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Provider;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Provider\FreightClassesProvider;
use OroB2B\Bundle\ShippingBundle\Extension\FreightClassesExtensionInterface;

class FreightClassesProviderTest extends MeasureUnitProviderTest
{
    /** @var FreightClassesProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->provider = new FreightClassesProvider(
            $this->repository,
            $this->configManager,
            self::CONFIG_ENTRY_NAME
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getFreightClassesProvider
     */
    public function testGetFreightClasses(array $inputData, array $expectedData)
    {
        $this->repository->expects($this->once())->method('findAll')->willReturn($inputData['units']);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(self::CONFIG_ENTRY_NAME, false)
            ->willReturn($inputData['configUnits']);

        foreach ($inputData['extensions'] as $name => $extension) {
            $this->provider->addExtension($name, $extension);
        }

        $this->assertEquals($expectedData, array_values($this->provider->getFreightClasses($inputData['options'])));
    }

    /**
     * @return array
     */
    public function getFreightClassesProvider()
    {
        return [
            'no providers' => [
                'input' => [
                    'extensions' => [],
                    'options' => new ProductShippingOptions(),
                    'configUnits' => ['class50', 'class55'],
                    'units' => [
                        $this->getFreightClass('class50'),
                        $this->getFreightClass('class55'),
                    ],
                ],
                'expected' => [
                    $this->getFreightClass('class50'),
                    $this->getFreightClass('class55'),
                ],
            ],
            'existing extensions' => [
                'input' => [
                    'extensions' => [
                        'extension1' => $this->getClassesExtension(['class50']),
                        'extension2' => $this->getClassesExtension(['class60']),
                    ],
                    'options' => new ProductShippingOptions(),
                    'configUnits' => ['class50', 'class55', 'class60'],
                    'units' => [
                        $this->getFreightClass('class50'),
                        $this->getFreightClass('class55'),
                        $this->getFreightClass('class60'),
                    ],
                ],
                'expected' => [
                    $this->getFreightClass('class50'),
                    $this->getFreightClass('class60'),
                ],
            ],
        ];
    }

    /**
     * @param array $classes
     * @return FreightClassesExtensionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getClassesExtension(array $classes)
    {
        $extension = $this->getMock('OroB2B\Bundle\ShippingBundle\Extension\FreightClassesExtensionInterface');
        $extension->expects($this->any())
            ->method('isApplicable')
            ->willReturnCallback(function (FreightClass $class, ProductShippingOptions $options) use ($classes) {
                return in_array($class->getCode(), $classes, true);
            });

        return $extension;
    }

    /**
     * @param string $code
     * @return FreightClass
     */
    protected function getFreightClass($code)
    {
        $class = new FreightClass();
        $class->setCode($code);

        return $class;
    }
}
