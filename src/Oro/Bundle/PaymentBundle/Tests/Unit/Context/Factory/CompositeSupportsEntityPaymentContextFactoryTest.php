<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\Factory;

use Oro\Bundle\PaymentBundle\Context\Factory\CompositeSupportsEntityPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\Factory\SupportsEntityPaymentContextFactoryInterface;

class CompositeSupportsEntityPaymentContextFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CompositeSupportsEntityPaymentContextFactory
     */
    private $factory;

    const ENTITY_CLASS_ONE = 'OrderFQCN';
    const ENTITY_ID_ONE = 1;
    const ENTITY_CLASS_TWO = 'InvoiceFQCN';
    const ENTITY_ID_TWO = 2;

    /**
     * @var array
     */
    protected $results;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->results = [
            'resultOne' => new \stdClass(),
            'resultTwo' => new \stdClass(),
        ];

        $factoryOne = $this->createMock(SupportsEntityPaymentContextFactoryInterface::class);
        $factoryOne
            ->method('supports')
            ->willReturnMap([
                [self::ENTITY_CLASS_ONE, self::ENTITY_ID_ONE, true],
                [self::ENTITY_CLASS_TWO, self::ENTITY_ID_TWO, false],
            ]);
        $factoryOne
            ->method('create')
            ->with(self::ENTITY_CLASS_ONE, self::ENTITY_ID_ONE)
            ->willReturn($this->results['resultOne']);

        $factoryTwo = $this->createMock(SupportsEntityPaymentContextFactoryInterface::class);
        $factoryTwo
            ->method('supports')
            ->willReturnMap([
                [self::ENTITY_CLASS_ONE, self::ENTITY_ID_ONE, false],
                [self::ENTITY_CLASS_TWO, self::ENTITY_ID_TWO, true],
            ]);
        $factoryTwo
            ->method('create')
            ->with(self::ENTITY_CLASS_TWO, self::ENTITY_ID_TWO)
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
            [self::ENTITY_CLASS_ONE, self::ENTITY_ID_ONE, 'resultOne'],
            [self::ENTITY_CLASS_TWO, self::ENTITY_ID_TWO, 'resultTwo'],
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
            'with first supported entity' => [self::ENTITY_CLASS_ONE, self::ENTITY_ID_ONE, true],
            'with second supported entity' => [self::ENTITY_CLASS_TWO, self::ENTITY_ID_TWO, true],
            'with unsupported entity' => ['UnsupportedEntityClass', 1, false],
        ];
    }
}
