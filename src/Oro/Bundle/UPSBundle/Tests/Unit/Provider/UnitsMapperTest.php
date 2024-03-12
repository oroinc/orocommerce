<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Provider\UnitsMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UnitsMapperTest extends TestCase
{
    /** @var ManagerRegistry|MockObject */
    private $registry;

    /** @var UnitsMapper */
    private $mapper;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->mapper = new UnitsMapper($this->registry);
    }

    public function testGetUPSUnitCodeWrong()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This unit is not accepted by UPS: wrong_code.');

        $this->mapper->getUPSUnitByCode('wrong_code');
    }

    public function testGetUPSUnitCode()
    {
        self::assertEquals(
            UPSTransport::UNIT_OF_WEIGHT_KGS,
            $this->mapper->getUPSUnitByCode(UnitsMapper::UNIT_OF_WEIGHT_KG)
        );
    }

    public function testGetUPSUnitCodeWithRealOROCodeException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This unit is not accepted by UPS: foot.');

        $this->mapper->getUPSUnitByCode(UnitsMapper::UNIT_OF_LENGTH_FOOT);
    }

    public function testGetUPSUnit()
    {
        self::assertEquals(
            UPSTransport::UNIT_OF_WEIGHT_KGS,
            $this->mapper->getUPSUnitByShippingUnit((new WeightUnit())->setCode('kg'))
        );
    }

    public function testGetShippingUnitCodeWrong()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This unit is not UPS unit: wrong_code.');

        $this->mapper->getShippingUnitCode('wrong_code');
    }

    public function testGetOROUnitCode()
    {
        self::assertEquals(
            UnitsMapper::UNIT_OF_WEIGHT_KG,
            $this->mapper->getShippingUnitCode(UPSTransport::UNIT_OF_WEIGHT_KGS)
        );
    }

    public function testGetOROUnitWeight()
    {
        $kgUnit = (new WeightUnit())->setCode('kg');

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::any())
            ->method('findOneBy')
            ->willReturn($kgUnit);
        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(WeightUnit::class)
            ->willReturn($repository);

        self::assertEquals(
            $kgUnit,
            $this->mapper->getShippingUnitByUPSUnit(UPSTransport::UNIT_OF_WEIGHT_KGS)
        );
    }

    public function testGetOROUnitLength()
    {
        $kgUnit = (new WeightUnit())->setCode('inch');

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::any())
            ->method('findOneBy')
            ->willReturn($kgUnit);
        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(LengthUnit::class)
            ->willReturn($repository);

        self::assertEquals(
            $kgUnit,
            $this->mapper->getShippingUnitByUPSUnit(UPSTransport::UNIT_OF_LENGTH_INCH)
        );
    }
}
