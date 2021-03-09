<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Validator\Constraints\PriceForProductUnitExists;
use Oro\Bundle\PricingBundle\Validator\Constraints\PriceForProductUnitExistsValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Context\ExecutionContext;

class PriceForProductUnitExistsValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function testValidationWithError()
    {
        /** @var ExecutionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unit = (new ProductUnit())->setCode('item');
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($unit);
        $elements = [$productUnitPrecision];
        $product = (new Product())->setPrimaryUnitPrecision($productUnitPrecision);
        $value = $this->createPersistentCollectionForUnitPrecisions($elements);
        $price = (new Price())->setValue('22');

        // remove precision
        // now collection has deleted element
        $value->removeElement($productUnitPrecision);
        $repository = $this->createMock(ObjectRepository::class);
        $em = $this->createMock(ObjectManager::class);
        $em->method('getRepository')->willReturn($repository);
        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $form = $this->createMock(FormInterface::class);
        $childForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $form->expects($this->once())
            ->method('get')
            ->willReturn($childForm);

        $childForm->expects($this->once())
            ->method('getData')
            ->willReturn(
                [
                    [
                        (new PriceAttributeProductPrice())->setProduct($product)->setUnit($unit)->setPrice($price)
                    ],
                ]
            );

        $context->expects($this->once())
            ->method('getRoot')
            ->willReturn($form);

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

    public function testValidationWithoutErrorsWhenEmptyPriceValue()
    {
        /** @var ExecutionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unit = (new ProductUnit())->setCode('item');
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($unit);
        $elements = [$productUnitPrecision];
        $product = (new Product())->setPrimaryUnitPrecision($productUnitPrecision);
        $value = $this->createPersistentCollectionForUnitPrecisions($elements);
        $price = (new Price())->setValue(null);

        // remove precision
        // now collection has deleted element
        $value->removeElement($productUnitPrecision);
        $repository = $this->createMock(ObjectRepository::class);
        $em = $this->createMock(ObjectManager::class);
        $em->method('getRepository')->willReturn($repository);
        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $form = $this->createMock(FormInterface::class);

        $form->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $childForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('get')
            ->willReturn($childForm);

        $childForm->expects($this->once())
            ->method('getData')
            ->willReturn(
                [
                    [
                        (new PriceAttributeProductPrice())->setProduct($product)->setUnit($unit)->setPrice($price)
                    ],
                ]
            );

        $context->expects($this->once())
            ->method('getRoot')
            ->willReturn($form);

        // prices for deleted product unit not exist, violation shouldn't be added
        $repository->expects($this->never())
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

    public function testValidationWithoutErrorsWhenHaveNoPricesInDb()
    {
        /** @var ExecutionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unit = (new ProductUnit())->setCode('item');
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($unit);
        $elements = [$productUnitPrecision];
        $product = (new Product())->setPrimaryUnitPrecision($productUnitPrecision);
        $value = $this->createPersistentCollectionForUnitPrecisions($elements);
        $price = (new Price())->setValue('22');

        // remove precision
        // now collection has deleted element
        $value->removeElement($productUnitPrecision);
        $repository = $this->createMock(ObjectRepository::class);
        $em = $this->createMock(ObjectManager::class);
        $em->method('getRepository')->willReturn($repository);
        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $form = $this->createMock(FormInterface::class);
        $childForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('get')
            ->willReturn($childForm);

        $form->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $childForm->expects($this->once())
            ->method('getData')
            ->willReturn(
                [
                    [
                        (new PriceAttributeProductPrice())->setProduct($product)->setUnit($unit)->setPrice($price)
                    ],
                ]
            );

        $context->expects($this->once())
            ->method('getRoot')
            ->willReturn($form);

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
     * @return PersistentCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createPersistentCollectionForUnitPrecisions($elements)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->createMock(EntityManagerInterface::class);
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        $collection = new ArrayCollection($elements);
        $value = new PersistentCollection($em, $classMetadata, $collection);
        $value->takeSnapshot();

        return $value;
    }
}
