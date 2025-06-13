<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\ImportExport\Converter;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\ImportExport\Converter\OrderShippingMethodConverter;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodTypeStub;
use Oro\Bundle\ShippingBundle\Translator\ShippingMethodLabelTranslator;
use PHPUnit\Framework\TestCase;

class OrderShippingMethodConverterTest extends TestCase
{
    private OrderShippingMethodConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        $shippingMethodProvider->expects(self::any())
            ->method('getShippingMethods')
            ->willReturn([
                $this->getShippingMethod('method_1', ['primary']),
                $this->getShippingMethod('method_2', ['type_1', 'type_2'])
            ]);

        $shippingMethodLabelTranslator = $this->createMock(ShippingMethodLabelTranslator::class);
        $shippingMethodLabelTranslator->expects(self::any())
            ->method('getShippingMethodWithTypeLabel')
            ->willReturnCallback(function (?string $shippingMethod, ?string $shippingMethodType) {
                return str_replace('_', ' ', \sprintf('%s, %s', $shippingMethod, $shippingMethodType));
            });

        $this->converter = new OrderShippingMethodConverter(
            $shippingMethodProvider,
            $shippingMethodLabelTranslator
        );
    }

    private function getShippingMethod(string $method, array $types): ShippingMethodStub
    {
        $shippingMethod = new ShippingMethodStub();
        $shippingMethod->setIdentifier($method);
        $shippingMethodTypes = [];
        foreach ($types as $type) {
            $shippingMethodType = new ShippingMethodTypeStub();
            $shippingMethodType->setIdentifier($type);
            $shippingMethodTypes[] = $shippingMethodType;
        }
        $shippingMethod->setTypes($shippingMethodTypes);

        return $shippingMethod;
    }

    public function testConvertForExistingShippingMethod(): void
    {
        $item = [
            'entity' => [
                'attributes' => [
                    'name' => 'Name'
                ]
            ]
        ];
        $sourceData = [
            'shippingMethod' => 'method 2, type 1'
        ];

        self::assertEquals(
            [
                'entity' => [
                    'attributes' => [
                        'name' => 'Name',
                        'shippingMethod' => 'method_2',
                        'shippingMethodType' => 'type_1'
                    ]
                ]
            ],
            $this->converter->convert($item, $sourceData)
        );
    }

    public function testConvertForNotExistingShippingMethod(): void
    {
        $item = [
            'entity' => [
                'attributes' => [
                    'name' => 'Name'
                ]
            ]
        ];
        $sourceData = [
            'shippingMethod' => 'method 2, type 3'
        ];

        self::assertEquals($item, $this->converter->convert($item, $sourceData));
    }

    public function testConvertWithoutShippingMethodInSourceData(): void
    {
        $item = [
            'entity' => [
                'attributes' => [
                    'name' => 'Name'
                ]
            ]
        ];
        $sourceData = [];

        self::assertEquals($item, $this->converter->convert($item, $sourceData));
    }

    public function testConvertWithInvalidShippingMethodInSourceData(): void
    {
        $item = [
            'entity' => [
                'attributes' => [
                    'name' => 'Name'
                ]
            ]
        ];
        $sourceData = [
            'shippingMethod' => 123
        ];

        self::assertEquals($item, $this->converter->convert($item, $sourceData));
    }

    public function testReverseConvertWithShippingMethodInSourceEntity(): void
    {
        $item = [
            'name' => 'Name'
        ];
        $sourceEntity = new Order();
        $sourceEntity->setShippingMethod('method_2');
        $sourceEntity->setShippingMethodType('type_1');

        self::assertEquals(
            [
                'name' => 'Name',
                'shippingMethod' => 'method 2, type 1'
            ],
            $this->converter->reverseConvert($item, $sourceEntity)
        );
    }

    public function testReverseConvertWhenNoShippingMethodInSourceEntity(): void
    {
        $item = [
            'name' => 'Name'
        ];
        $sourceEntity = new Order();

        self::assertEquals($item, $this->converter->reverseConvert($item, $sourceEntity));
    }
}
