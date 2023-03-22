<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Factory;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Mapper\UnmappableArgumentException;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class TaxFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaxMapperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $mapper1;

    /** @var TaxFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->mapper1 = $this->createMock(TaxMapperInterface::class);

        $mappers = TestContainerBuilder::create()
            ->add(Order::class, $this->mapper1)
            ->getContainer($this);

        $this->factory = new TaxFactory($mappers);
    }

    public function testCreate()
    {
        $object = new Order();

        $this->mapper1->expects($this->exactly(2))
            ->method('map')
            ->with($this->identicalTo($object))
            ->willReturnCallback(function () {
                return new Taxable();
            });

        $taxable = $this->factory->create($object);
        $this->assertInstanceOf(Taxable::class, $taxable);

        $anotherTaxable = $this->factory->create($object);
        $this->assertInstanceOf(Taxable::class, $anotherTaxable);

        $this->assertNotSame($taxable, $anotherTaxable);
    }

    public function testCreateThrowExceptionWithoutMapper()
    {
        $this->expectException(UnmappableArgumentException::class);
        $this->expectExceptionMessage('Can\'t find Tax Mapper for object "stdClass"');

        $this->factory->create(new \stdClass());
    }

    public function testSupports()
    {
        $this->assertTrue($this->factory->supports(new Order()));
        $this->assertFalse($this->factory->supports(new \stdClass()));
    }
}
