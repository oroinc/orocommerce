<?php

namespace Oro\Bundle\PaymentBundle\Test\Unit\Context\Factory;

use Oro\Bundle\PaymentBundle\Context\Factory\CompositeSupportsEntityPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\Factory\SupportsEntityPaymentContextFactoryInterface;

class CompositeSupportsEntityPaymentContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CompositeSupportsEntityPaymentContextFactory
     */
    protected $factory;

    protected $entityClassOne = 'OrderFQCN';
    protected $entityClassTwo = 'InvoiceFQCN';
    protected $entityIdOne = 1;
    protected $entityIdTwo = 2;

    /**
     * @var array
     */
    protected $results;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->results = [
            'resultOne' => new \stdClass(),
            'resultTwo' => new \stdClass(),
        ];

        $factoryOne = $this->createMock(SupportsEntityPaymentContextFactoryInterface::class);
        $factoryOne
            ->method('supports')
            ->willReturnMap([
                [$this->entityClassOne, $this->entityIdOne, true],
                [$this->entityClassTwo, $this->entityIdTwo, false],
            ]);
        $factoryOne
            ->method('create')
            ->with($this->entityClassOne, $this->entityIdOne)
            ->willReturn($this->results['resultOne']);

        $factoryTwo = $this->createMock(SupportsEntityPaymentContextFactoryInterface::class);
        $factoryTwo
            ->method('supports')
            ->willReturnMap([
                [$this->entityClassOne, $this->entityIdOne, false],
                [$this->entityClassTwo, $this->entityIdTwo, true],
            ]);
        $factoryTwo
            ->method('create')
            ->with($this->entityClassTwo, $this->entityIdTwo)
            ->willReturn($this->results['resultTwo']);

        $factories = [$factoryOne, $factoryTwo];

        $this->factory = new CompositeSupportsEntityPaymentContextFactory($factories);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string $entityClass
     * @param int $entityId
     * @param string $expectedResult
     */
    public function testCreate($entityClass, $entityId, $expectedResult)
    {
        $result = $this->factory->create($entityClass, $entityId);

        static::assertSame($this->results[$expectedResult], $result);
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            [$this->entityClassOne, $this->entityIdOne, 'resultOne'],
            [$this->entityClassTwo, $this->entityIdTwo, 'resultTwo'],
        ];
    }

    /**
     * @dataProvider supportsWithSupportedClassDataProvider
     *
     * @param string $entityClass
     * @param int $entityId
     * @param bool $expected
     */
    public function testSupports($entityClass, $entityId, $expected)
    {
        $actual = $this->factory->supports($entityClass, $entityId);
        static::assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function supportsWithSupportedClassDataProvider()
    {
        return [
            'with first supported entity' => [$this->entityClassOne, $this->entityIdOne, true],
            'with second supported entity' => [$this->entityClassTwo, $this->entityIdTwo, true],
            'with unsupported entity' => ['UnsupportedEntityClass', 1, false],
        ];
    }
}
