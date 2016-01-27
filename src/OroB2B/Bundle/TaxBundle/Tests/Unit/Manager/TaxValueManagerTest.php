<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Manager\TaxValueManager;

class TaxValueManagerTest extends \PHPUnit_Framework_TestCase
{
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
            'OroB2B\Bundle\TaxBundle\Entity\TaxValue',
            'OroB2B\Bundle\TaxBundle\Entity\Tax'
        );
    }

    public function testGetTaxValue()
    {
        $class = 'OroB2B\Bundle\TaxBundle\Entity\TaxValue';
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
        $class = 'OroB2B\Bundle\TaxBundle\Entity\TaxValue';
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
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Entity\TaxValue', $taxValue);

        // cache
        $this->assertSame($taxValue, $this->manager->getTaxValue($class, $id));
    }

    public function testSaveTaxValue()
    {
        $taxValue = new TaxValue();

        $em = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $em->expects($this->once())->method('persist')->with($taxValue);
        $em->expects($this->once())->method('flush')->with($taxValue);
        $this->doctrineHelper->expects($this->once())->method('getEntityManager')->willReturn($em);

        $this->manager->saveTaxValue($taxValue);
    }

    public function testProxyGetReference()
    {
        $code = 'code';

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $repo->expects($this->once())->method('findOneBy')->with(['code' => 'code']);

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')
            ->with('OroB2B\Bundle\TaxBundle\Entity\Tax')->willReturn($repo);

        $this->manager->getTax($code);
    }
}
