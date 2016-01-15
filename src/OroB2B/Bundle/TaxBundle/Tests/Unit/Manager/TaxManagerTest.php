<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Manager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Factory\TaxFactory;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface;

class TaxManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TaxManager */
    protected $manager;

    /**  @var \PHPUnit_Framework_MockObject_MockObject|TaxFactory */
    protected $factory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Factory\TaxFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())->method('getEntityClass')->willReturnCallback(
            function ($object) {
                $this->assertInternalType('object', $object);

                return get_class($object);
            }
        );

        $this->manager = new TaxManager($this->factory, $this->eventDispatcher, $this->doctrineHelper, '\stdClass');
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
     * @expectedExceptionMessage Object identifier is missing
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

    public function testTaxValueMissing()
    {
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
        $repository->expects($this->once())->method('findOneBy')->with($this->isType('array'))->willReturn(null);
        $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')->willReturn($repository);

        $result = $this->manager->loadTax(new \stdClass());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $result);
        $this->assertEquals(new Result(), $result);
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

        $result = $this->manager->loadTax(new \stdClass());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $result);
        $this->assertSame($taxResult, $result);
    }

    public function testGetTaxNewResult()
    {
        $taxable = new Taxable();
        $this->factory->expects($this->once())->method('create')->willReturn($taxable);

        $this->doctrineHelper->expects($this->never())->method('getEntityRepositoryForClass');
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifier');

        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->with(
                ResolveTaxEvent::NAME,
                $this->callback(
                    function ($event) use ($taxable) {
                        /** @var ResolveTaxEvent $event */
                        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent', $event);
                        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Taxable', $event->getTaxable());
                        $this->assertSame($taxable, $event->getTaxable());
                        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $event->getResult());

                        $unit = $event->getResult()->getUnit();
                        $unit->offsetSet(ResultElement::EXCLUDING_TAX, 20);
                        $event->getResult()->offsetSet(Result::UNIT, $unit);

                        return true;
                    }
                )
            );

        $result = $this->manager->getTax(new \stdClass());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $result);
        $this->assertEquals(20, $result->getUnit()->getExcludingTax());
        $this->assertEquals(null, $result->getUnit()->getIncludingTax());
    }

    public function testGetTaxLoadResult()
    {
        $taxValue = new TaxValue();
        $taxResult = new Result([Result::ROW => new ResultElement([ResultElement::EXCLUDING_TAX => 10])]);
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

        $taxable = new Taxable();
        $this->factory->expects($this->once())->method('create')->willReturn($taxable);

        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->with(
                ResolveTaxEvent::NAME,
                $this->callback(
                    function ($event) use ($taxable, $taxResult) {
                        /** @var ResolveTaxEvent $event */
                        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent', $event);
                        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Taxable', $event->getTaxable());
                        $this->assertSame($taxable, $event->getTaxable());
                        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $event->getResult());
                        $this->assertSame($taxResult, $event->getResult());

                        $unit = $event->getResult()->getUnit();
                        $unit->offsetSet(ResultElement::EXCLUDING_TAX, 20);
                        $event->getResult()->offsetSet(Result::UNIT, $unit);

                        return true;
                    }
                )
            );

        $result = $this->manager->getTax(new \stdClass());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $result);
        $this->assertEquals(20, $result->getUnit()->getExcludingTax());
        $this->assertEquals(null, $result->getUnit()->getIncludingTax());
        $this->assertEquals(10, $result->getRow()->getExcludingTax());
        $this->assertEquals(null, $result->getRow()->getIncludingTax());
    }

    public function testSave()
    {
        $entity = new \stdClass();

        /** @var \PHPUnit_Framework_MockObject_MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->getMock('OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $transformer->expects($this->once())->method('reverseTransform')->willReturnCallback(
            function (TaxValue $taxValue, Result $result) {
                $taxValue->setResult($result);

                return $taxValue;
            }
        );
        $transformer->expects($this->once())->method('transform')->willReturnCallback(
            function (TaxValue $taxValue) {
                return $taxValue->getResult();
            }
        );
        $this->manager->addTransformer('stdClass', $transformer);

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $taxValue = new TaxValue();
        $repository->expects($this->once())->method('findOneBy')->with($this->isType('array'))->willReturn($taxValue);
        $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')->willReturn($repository);
        $this->doctrineHelper->expects($this->exactly(2))->method('getSingleEntityIdentifier')->willReturn(1);

        $taxable = new Taxable();
        $this->factory->expects($this->once())->method('create')->willReturn($taxable);

        $em = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $em->expects($this->once())->method('persist')->with($taxValue);
        $em->expects($this->once())->method('flush')->with($taxValue);
        $this->doctrineHelper->expects($this->once())->method('getEntityManager')->willReturn($em);

        $this->manager->saveTax($entity);
    }
}
