<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Provider;

use OroB2B\Bundle\ShippingBundle\Provider\FreightClassesProvider;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Provider\FreightClassesProviderInterface;

class FreightClassesProviderTest extends MeasureUnitProviderTest
{
    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getUnitsByProductShippingOptionsProvider
     */
    public function testGetUnitsByProductShippingOptions(array $inputData, array $expectedData)
    {
        $provider = $this->createProvider($inputData['units'], $inputData['configUnits'], $inputData['providers']);

        $this->assertEquals(
            array_values($expectedData),
            $provider->getUnitsByProductShippingOptions($inputData['options'])
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getUnitsByProductShippingOptionsProvider
     */
    public function testGetUnitsCodesByProductShippingOptions(array $inputData, array $expectedData)
    {
        $provider = $this->createProvider($inputData['units'], $inputData['configUnits'], $inputData['providers']);

        $this->assertEquals(
            array_keys($expectedData),
            $provider->getUnitsCodesByProductShippingOptions($inputData['options'])
        );
    }

    /**
     * @return array
     */
    public function getUnitsByProductShippingOptionsProvider()
    {
        return [
            'no providers' => [
                'input' => [
                    'providers' => [],
                    'options' => new ProductShippingOptions(),
                    'configUnits' => ['class50', 'class55'],
                    'units' => [
                        $this->getFreightClass('class50'),
                        $this->getFreightClass('class55'),
                    ],
                ],
                'expected' => [
                    'class50' => $this->getFreightClass('class50'),
                    'class55' => $this->getFreightClass('class55'),
                ],
            ],
            'existing providers' => [
                'input' => [
                    'providers' => [
                        'provider1' => $this->getClassesProvider(
                            [
                                $this->getFreightClass('class50'),
                                $this->getFreightClass('class55'),
                                $this->getFreightClass('class60'),
                            ],
                            new ProductShippingOptions(),
                            [
                                $this->getFreightClass('class50'),
                            ]
                        ),
                        'provider2' => $this->getClassesProvider(
                            [
                                $this->getFreightClass('class50'),
                                $this->getFreightClass('class55'),
                                $this->getFreightClass('class60'),
                            ],
                            new ProductShippingOptions(),
                            [
                                $this->getFreightClass('class60'),
                            ]
                        ),
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
                    'class50' => $this->getFreightClass('class50'),
                    'class60' => $this->getFreightClass('class60'),
                ],
            ],
        ];
    }

    /**
     * @param array $units
     * @param array $configUnits
     * @param array $providers
     * @return FreightClassesProvider
     */
    private function createProvider(array $units, array $configUnits, array $providers)
    {
        $this->repo->expects($this->once())
            ->method('findAll')
            ->willReturn($units);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with('entityClass')
            ->willReturn($this->repo);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('configKey')
            ->willReturn($configUnits);

        $provider = new FreightClassesProvider($this->doctrineHelper, $this->configManager);
        $provider->setEntityClass('entityClass');
        $provider->setConfigEntryName('configKey');
        $provider->setLabelFormatter($this->labelFormatter);

        foreach ($providers as $name => $subProvider) {
            $provider->addProvider($name, $subProvider);
        }

        return $provider;
    }

    /**
     * @param array $input
     * @param ProductShippingOptions $options
     * @param array $output
     * @return FreightClassesProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getClassesProvider(array $input, ProductShippingOptions $options, array $output)
    {
        $provider = $this->getMock('OroB2B\Bundle\ShippingBundle\Provider\FreightClassesProviderInterface');
        $provider->expects($this->once())
            ->method('getFreightClasses')
            ->with($input, $options)
            ->willReturn($output);

        return $provider;
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
