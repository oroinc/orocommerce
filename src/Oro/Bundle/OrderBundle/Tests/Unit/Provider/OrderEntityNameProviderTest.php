<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderEntityNameProvider;

class OrderEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderEntityNameProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new OrderEntityNameProvider();
    }

    /**
     * @dataProvider getNameDataProvider
     */
    public function testGetName(string $format, ?string $locale, object $entity, string|false $expected)
    {
        $this->assertEquals($expected, $this->provider->getName($format, $locale, $entity));
    }

    public function getNameDataProvider(): array
    {
        $order = new Order();
        $order->setPoNumber('po_number')->setIdentifier('identifier')->setCurrency('USD');

        return [
            'test unsupported class' => [
                'format' => '',
                'locale' => null,
                'entity' => new \stdClass(),
                'expected' => false
            ],
            'test unsupported format' => [
                'format' => '',
                'locale' => null,
                'entity' => $order,
                'expected' => false
            ],
            'correct data' => [
                'format' => EntityNameProviderInterface::FULL,
                'locale' => '',
                'entity' => $order,
                'expected' => 'identifier po_number USD'
            ]
        ];
    }

    /**
     * @dataProvider getNameDQLDataProvider
     */
    public function testGetNameDQL(
        string $format,
        ?string $locale,
        string $className,
        string $alias,
        string|false $expected
    ) {
        $this->assertEquals($expected, $this->provider->getNameDQL($format, $locale, $className, $alias));
    }

    public function getNameDQLDataProvider(): array
    {
        return [
            'test unsupported class Name' => [
                'format' => '',
                'locale' => null,
                'className' => '',
                'alias' => '',
                'expected' => false
            ],
            'test unsupported format' => [
                'format' => '',
                'locale' => null,
                'className' => Order::class,
                'alias' => '',
                'expected' => false
            ],
            'correct data' => [
                'format' => EntityNameProviderInterface::FULL,
                'locale' => null,
                'className' => Order::class,
                'alias' => 'test',
                'expected' => 'test.identifier'
            ]
        ];
    }
}
