<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Validator\Context\ExecutionContext;

use OroB2B\Bundle\PricingBundle\Validator\Constraints\PriceForProductUnitExists;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\PriceForProductUnitExistsValidator;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

class PriceForProductUnitExistsValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testValidationWithError()
    {
        /** @var ExecutionContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unit = (new ProductUnit())->setCode('item');
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($unit);
        $elements = [$productUnitPrecision];
        $value = $this->createPersistentCollectionForUnitPrecisions($elements);

        // remove precision
        // now collection has deleted element
        $value->removeElement($productUnitPrecision);
        $repository = $this->getMock(ObjectRepository::class);
        $em = $this->getMock(ObjectManager::class);
        $em->method('getRepository')->willReturn($repository);
        /** @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMock(RegistryInterface::class);
        $registry->method('getManagerForClass')->willReturn($em);

        // prices for deleted product unit exist, violation should be added
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['product' => null, 'unit' => [$unit]])
            ->willReturn([new PriceAttributeProductPrice()]);

        $validator = new PriceForProductUnitExistsValidator($registry);
        $validator->initialize($context);
        $constraint = new PriceForProductUnitExists();

        // expect validation error added
        $context->expects($this->once())
            ->method('addViolation')
            ->with($constraint->message, ['%units%' => 'item']);

        $validator->validate($value, $constraint);
    }

    public function testValidationWithoutErrors()
    {
        /** @var ExecutionContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unit = (new ProductUnit())->setCode('item');
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($unit);
        $elements = [$productUnitPrecision];
        $value = $this->createPersistentCollectionForUnitPrecisions($elements);

        // remove precision
        // now collection has deleted element
        $value->removeElement($productUnitPrecision);
        $repository = $this->getMock(ObjectRepository::class);
        $em = $this->getMock(ObjectManager::class);
        $em->method('getRepository')->willReturn($repository);
        /** @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMock(RegistryInterface::class);
        $registry->method('getManagerForClass')->willReturn($em);

        // prices for deleted product unit not exist, violation shouldn't be added
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['product' => null, 'unit' => [$unit]])
            ->willReturn([]);

        $validator = new PriceForProductUnitExistsValidator($registry);
        $validator->initialize($context);
        $constraint = new PriceForProductUnitExists();

        // expect validation error not added
        $context->expects($this->never())
            ->method('addViolation');

        $validator->validate($value, $constraint);
    }

    /**
     * @param $elements
     * @return PersistentCollection
     */
    protected function createPersistentCollectionForUnitPrecisions($elements)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getMock(EntityManagerInterface::class);
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        $collection = new ArrayCollection($elements);
        $value = new PersistentCollection($em, $classMetadata, $collection);
        $value->takeSnapshot();

        return $value;
    }
}
