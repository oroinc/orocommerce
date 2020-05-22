<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderEntityNameProvider;

class OrderEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderEntityNameProvider */
    protected $provider;

    protected function setUp(): void
    {
        $this->provider = new OrderEntityNameProvider();
    }

    /**
     * @dataProvider getNameDataProvider
     *
     * @param string $format
     * @param string $locale
     * @param string $entity
     * @param bool|string $expected
     */
    public function testGetName($format, $locale, $entity, $expected)
    {
        $this->assertEquals($expected, $this->provider->getName($format, $locale, $entity));
    }

    /**
     * @return \Generator
     */
    public function getNameDataProvider()
    {
        $order = new Order();
        $order->setPoNumber('po_number')->setIdentifier('identifier')->setCurrency('USD');

        yield 'test unsupported class' => [
            'format' => '',
            'locale' => null,
            'entity' => new \stdClass(),
            'expected' => false
        ];

        yield 'test unsupported format' => [
            'format' => '',
            'locale' => null,
            'entity' => $order,
            'expected' => false
        ];

        yield 'correct data' => [
            'format' => EntityNameProviderInterface::FULL,
            'locale' => '',
            'entity' => $order,
            'expected' => 'identifier po_number USD'
        ];
    }

    /**
     * @dataProvider getNameDQLDataProvider
     *
     * @param string $format
     * @param string $locale
     * @param string $className
     * @param string $alias
     * @param bool|string $expected
     */
    public function testGetNameDQL($format, $locale, $className, $alias, $expected)
    {
        $this->assertEquals($expected, $this->provider->getNameDQL($format, $locale, $className, $alias));
    }

    /**
     * @return \Generator
     */
    public function getNameDQLDataProvider()
    {
        yield 'test unsupported class Name' => [
            'format' => '',
            'locale' => null,
            'className' => '',
            'alias' => '',
            'expected' => false
        ];

        yield 'test unsupported format' => [
            'format' => '',
            'locale' => null,
            'className' => Order::class,
            'alias' => '',
            'expected' => false
        ];

        yield 'correct data' => [
            'format' => EntityNameProviderInterface::FULL,
            'locale' => null,
            'className' => Order::class,
            'alias' => 'test',
            'expected' => 'test.identifier'
        ];
    }
}
