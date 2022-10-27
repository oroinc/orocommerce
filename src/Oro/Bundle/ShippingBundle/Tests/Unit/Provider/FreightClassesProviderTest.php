<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Extension\FreightClassesExtensionInterface;
use Oro\Bundle\ShippingBundle\Provider\FreightClassesProvider;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;

class FreightClassesProviderTest extends MeasureUnitProviderTest
{
    /** @var FreightClassesProvider */
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new FreightClassesProvider(
            $this->repository,
            $this->configManager,
            self::CONFIG_ENTRY_NAME
        );
    }

    /**
     * @dataProvider getFreightClassesProvider
     */
    public function testGetFreightClasses(array $inputData, array $expectedData)
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($inputData['units']);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(self::CONFIG_ENTRY_NAME, false)
            ->willReturn($inputData['configUnits']);

        if (null !== $inputData['extensions']) {
            $this->provider->setExtensions($inputData['extensions']);
        }

        $this->assertEquals($expectedData, array_values($this->provider->getFreightClasses($inputData['options'])));
    }

    public function getFreightClassesProvider(): array
    {
        return [
            'no providers' => [
                'input' => [
                    'extensions' => null,
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
                    'extensions' => new RewindableGenerator(
                        function () {
                            yield $this->getClassesExtension(['class50']);
                            yield $this->getClassesExtension(['class60']);
                        },
                        2
                    ),
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

    private function getClassesExtension(array $classes): FreightClassesExtensionInterface
    {
        $extension = $this->createMock(FreightClassesExtensionInterface::class);
        $extension->expects($this->any())
            ->method('isApplicable')
            ->willReturnCallback(function (FreightClass $class) use ($classes) {
                return in_array($class->getCode(), $classes, true);
            });

        return $extension;
    }

    private function getFreightClass(string $code): FreightClass
    {
        $class = new FreightClass();
        $class->setCode($code);

        return $class;
    }
}
