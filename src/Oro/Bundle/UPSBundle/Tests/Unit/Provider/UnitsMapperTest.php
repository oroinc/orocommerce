<?php
namespace Oro\Bundle\UPSBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Provider\UnitsMapper;
use Symfony\Bridge\Doctrine\RegistryInterface;

class UnitsMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var UnitsMapper
     */
    protected $mapper;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(RegistryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->mapper = new UnitsMapper($this->registry);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage This unit is not accepted by UPS: wrong_code.
     */
    public function testGetUPSUnitCodeWrong()
    {
        $this->mapper->getUPSUnitByCode('wrong_code');
    }

    public function testGetUPSUnitCode()
    {
        static::assertEquals(
            UPSTransport::UNIT_OF_WEIGHT_KGS,
            $this->mapper->getUPSUnitByCode(UnitsMapper::UNIT_OF_WEIGHT_KG)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage This unit is not accepted by UPS: foot.
     */
    public function testGetUPSUnitCodeWithRealOROCodeException()
    {
        $this->mapper->getUPSUnitByCode(UnitsMapper::UNIT_OF_LENGTH_FOOT);
    }


    public function testGetUPSUnit()
    {
        static::assertEquals(
            UPSTransport::UNIT_OF_WEIGHT_KGS,
            $this->mapper->getUPSUnitByShippingUnit((new WeightUnit())->setCode('kg'))
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage This unit is not UPS unit: wrong_code.
     */
    public function testGetShippingUnitCodeWrong()
    {
        $this->mapper->getShippingUnitCode('wrong_code');
    }

    public function testGetOROUnitCode()
    {
        static::assertEquals(
            UnitsMapper::UNIT_OF_WEIGHT_KG,
            $this->mapper->getShippingUnitCode(UPSTransport::UNIT_OF_WEIGHT_KGS)
        );
    }

    public function testGetOROUnitWeight()
    {
        $kgUnit = (new WeightUnit)->setCode('kg');

        $repository = $this->getMockBuilder(ObjectRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects(self::any())->method('findOneBy')->willReturn($kgUnit);
        $this->registry
            ->expects(self::once())
            ->method('getRepository')
            ->with('OroShippingBundle:WeightUnit')
            ->willReturn($repository);

        static::assertEquals(
            $kgUnit,
            $this->mapper->getShippingUnitByUPSUnit(UPSTransport::UNIT_OF_WEIGHT_KGS)
        );
    }

    public function testGetOROUnitLength()
    {
        $kgUnit = (new WeightUnit)->setCode('inch');

        $repository = $this->getMockBuilder(ObjectRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects(self::any())->method('findOneBy')->willReturn($kgUnit);
        $this->registry
            ->expects(self::once())
            ->method('getRepository')
            ->with('OroShippingBundle:LengthUnit')
            ->willReturn($repository);

        static::assertEquals(
            $kgUnit,
            $this->mapper->getShippingUnitByUPSUnit(UPSTransport::UNIT_OF_LENGTH_INCH)
        );
    }
}
