<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;

class TaxValueManagerTest extends \PHPUnit_Framework_TestCase
{
    const TAX_VALUE_CLASS = 'Oro\Bundle\TaxBundle\Entity\TaxValue';
    const TAX_CLASS = 'Oro\Bundle\TaxBundle\Entity\Tax';

    /** @var  TaxValueManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new TaxValueManager(
            $this->doctrineHelper,
            self::TAX_VALUE_CLASS,
            self::TAX_CLASS
        );
    }

    public function testGetTaxValue()
    {
        $class = 'Oro\Bundle\TaxBundle\Entity\TaxValue';
        $id = 1;
        $taxValue = new TaxValue();

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->once())->method('findOneBy')
            ->with(
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->contains($class),
                    $this->contains($id)
                )
            )
            ->willReturn($taxValue);
        $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')->willReturn($repository);

        $this->assertSame($taxValue, $this->manager->getTaxValue($class, $id));

        // cache
        $this->assertSame($taxValue, $this->manager->getTaxValue($class, $id));
    }

    public function testGetTaxValueNew()
    {
        $class = 'Oro\Bundle\TaxBundle\Entity\TaxValue';
        $id = 1;

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->once())->method('findOneBy')
            ->with(
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->contains($class),
                    $this->contains($id)
                )
            )
            ->willReturn(null);
        $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')->willReturn($repository);

        $taxValue = $this->manager->getTaxValue($class, $id);
        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Entity\TaxValue', $taxValue);

        // cache
        $this->assertSame($taxValue, $this->manager->getTaxValue($class, $id));
    }

    public function testSaveTaxValue()
    {
        $taxValue = new TaxValue();

        $em = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')->disableOriginalConstructor()->getMock();
        $uow->expects($this->once())->method('scheduleExtraUpdate')->with(
            $taxValue,
            ['result' => [null, $taxValue->getResult()]]
        );
        $em->expects($this->once())->method('persist')->with($taxValue);
        $em->expects($this->once())->method('flush')->with($taxValue);
        $em->expects($this->once())->method('getUnitOfWork')->willReturn($uow);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(self::TAX_VALUE_CLASS)
            ->willReturn($em);

        $this->manager->saveTaxValue($taxValue);
    }

    public function testProxyGetReference()
    {
        $code = 'code';

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $repo->expects($this->once())->method('findOneBy')->with(['code' => 'code']);

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')
            ->with('Oro\Bundle\TaxBundle\Entity\Tax')->willReturn($repo);

        $this->manager->getTax($code);
    }

    public function testClear()
    {
        $class = 'stdClass';
        $id = 1;
        $cachedTaxValue = new TaxValue();
        $notCachedTaxValue = new TaxValue();

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $repository->expects($this->exactly(2))
            ->method('findOneBy')
            ->with(
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->contains($class),
                    $this->contains($id)
                )
            )
            ->willReturnOnConsecutiveCalls($cachedTaxValue, $notCachedTaxValue);

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        $this->assertSame($cachedTaxValue, $this->manager->getTaxValue($class, $id));
        $this->assertSame($cachedTaxValue, $this->manager->getTaxValue($class, $id));
        $this->manager->clear();
        $this->assertSame($notCachedTaxValue, $this->manager->getTaxValue($class, $id));
    }

    /**
     * @dataProvider removeTaxValueProvider
     * @param bool $flush
     * @param bool $contains
     * @param bool $expectedResult
     */
    public function testRemoveTaxValue($flush, $contains, $expectedResult)
    {
        $taxValue = new TaxValue();

        $taxValueEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $taxValueEm->expects($this->once())
            ->method('contains')
            ->with($taxValue)
            ->willReturn($contains);

        $taxValueEm->expects($contains ? $this->once() : $this->never())
            ->method('remove')
            ->with($taxValue);

        $taxValueEm->expects($contains && $flush ? $this->once() : $this->never())
            ->method('flush')
            ->with($taxValue);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(self::TAX_VALUE_CLASS)
            ->willReturn($taxValueEm);

        $this->assertEquals($expectedResult, $this->manager->removeTaxValue($taxValue, $flush));
    }

    /**
     * @return array
     */
    public function removeTaxValueProvider()
    {
        return [
            [
                'flush' => true,
                'contains' => false,
                'expectedResult' => false
            ],
            [
                'flush' => true,
                'contains' => true,
                'expectedResult' => true
            ],
            [
                'flush' => false,
                'contains' => true,
                'expectedResult' => true
            ]
        ];
    }
}
