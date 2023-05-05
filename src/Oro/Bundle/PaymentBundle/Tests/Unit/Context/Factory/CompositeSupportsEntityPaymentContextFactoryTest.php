<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\Factory;

use Oro\Bundle\PaymentBundle\Context\Factory\CompositeSupportsEntityPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\Factory\SupportsEntityPaymentContextFactoryInterface;

class CompositeSupportsEntityPaymentContextFactoryTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS_ONE = 'OrderFQCN';
    private const ENTITY_ID_ONE = 1;
    private const ENTITY_CLASS_TWO = 'InvoiceFQCN';
    private const ENTITY_ID_TWO = 2;

    /** @var array */
    private $results;

    /** @var CompositeSupportsEntityPaymentContextFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->results = [
            'resultOne' => new \stdClass(),
            'resultTwo' => new \stdClass(),
        ];

        $factoryOne = $this->createMock(SupportsEntityPaymentContextFactoryInterface::class);
        $factoryOne->expects(self::any())
            ->method('supports')
            ->willReturnMap([
                [self::ENTITY_CLASS_ONE, self::ENTITY_ID_ONE, true],
                [self::ENTITY_CLASS_TWO, self::ENTITY_ID_TWO, false],
            ]);
        $factoryOne->expects(self::any())
            ->method('create')
            ->with(self::ENTITY_CLASS_ONE, self::ENTITY_ID_ONE)
            ->willReturn($this->results['resultOne']);

        $factoryTwo = $this->createMock(SupportsEntityPaymentContextFactoryInterface::class);
        $factoryTwo->expects(self::any())
            ->method('supports')
            ->willReturnMap([
                [self::ENTITY_CLASS_ONE, self::ENTITY_ID_ONE, false],
                [self::ENTITY_CLASS_TWO, self::ENTITY_ID_TWO, true],
            ]);
        $factoryTwo->expects(self::any())
            ->method('create')
            ->with(self::ENTITY_CLASS_TWO, self::ENTITY_ID_TWO)
            ->willReturn($this->results['resultTwo']);

        $factories = [$factoryOne, $factoryTwo];

        $this->factory = new CompositeSupportsEntityPaymentContextFactory($factories);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(string $entityClass, int $entityId, string $expectedResult)
    {
        $result = $this->factory->create($entityClass, $entityId);

        self::assertSame($this->results[$expectedResult], $result);
    }

    public function createDataProvider(): array
    {
        return [
            [self::ENTITY_CLASS_ONE, self::ENTITY_ID_ONE, 'resultOne'],
            [self::ENTITY_CLASS_TWO, self::ENTITY_ID_TWO, 'resultTwo'],
        ];
    }

    /**
     * @dataProvider supportsWithSupportedClassDataProvider
     */
    public function testSupports(string $entityClass, int $entityId, bool $expected)
    {
        $actual = $this->factory->supports($entityClass, $entityId);
        self::assertSame($expected, $actual);
    }

    public function supportsWithSupportedClassDataProvider(): array
    {
        return [
            'with first supported entity' => [self::ENTITY_CLASS_ONE, self::ENTITY_ID_ONE, true],
            'with second supported entity' => [self::ENTITY_CLASS_TWO, self::ENTITY_ID_TWO, true],
            'with unsupported entity' => ['UnsupportedEntityClass', 1, false],
        ];
    }
}
