<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface;

class TaxManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TaxManager */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntityClass')->willReturnCallback(
            function ($object) {
                $this->assertInternalType('object', $object);

                return get_class($object);
            }
        );

        $this->manager = new TaxManager($this->doctrineHelper, '\stdClass');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage TaxTransformerInterface is missing for stdClass
     */
    public function testTransformerNotFound()
    {
        $this->doctrineHelper->expects($this->never())->method('getEntityRepositoryForClass');
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifier');

        $this->manager->loadTax(new \stdClass());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Can't load TaxValue for new stdClass entity
     */
    public function testNewEntity()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->getMock('OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $this->manager->addTransformer('stdClass', $transformer);

        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->willReturn(null);
        $this->doctrineHelper->expects($this->never())->method('getEntityRepositoryForClass');

        $this->manager->loadTax(new \stdClass());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage TaxValue for stdClass#1 not found
     */
    public function testTaxValueMissing()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->getMock('OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $this->manager->addTransformer('stdClass', $transformer);

        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->willReturn(1);

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->once())->method('findOneBy')->with($this->isType('array'))->willReturn(null);
        $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')->willReturn($repository);

        $this->manager->loadTax(new \stdClass());
    }

    public function testTaxValue()
    {
        $taxValue = new TaxValue();
        $taxResult = new Result([Result::UNIT => new ResultElement([ResultElement::INCLUDING_TAX => 10])]);
        $taxValue->setResult($taxResult);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->getMock('OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $transformer->expects($this->once())->method('transform')->willReturnCallback(
            function (TaxValue $taxValue) {
                return $taxValue->getResult();
            }
        );
        $this->manager->addTransformer('stdClass', $transformer);

        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->willReturn(1);

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->once())->method('findOneBy')->with($this->isType('array'))->willReturn($taxValue);
        $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')->willReturn($repository);

        $this->assertSame($taxResult, $this->manager->loadTax(new \stdClass()));
    }
}
