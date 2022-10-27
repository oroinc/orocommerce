<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Validator\Constraints\PriceForProductUnitExists;
use Oro\Bundle\PricingBundle\Validator\Constraints\PriceForProductUnitExistsValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PriceForProductUnitExistsValidatorTest extends ConstraintValidatorTestCase
{
    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        $em = $this->createMock(ObjectManager::class);
        $em->expects(self::any())
            ->method('getRepository')
            ->with(PriceAttributeProductPrice::class)
            ->willReturn($this->repository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(PriceAttributeProductPrice::class)
            ->willReturn($em);

        return new PriceForProductUnitExistsValidator($doctrine);
    }

    public function testValidationWithError()
    {
        $unit = (new ProductUnit())->setCode('item');
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($unit);
        $elements = [$productUnitPrecision];
        $product = (new Product())->setPrimaryUnitPrecision($productUnitPrecision);
        $value = $this->createPersistentCollectionForUnitPrecisions($elements);
        $price = (new Price())->setValue('22');

        // remove precision
        // now collection has deleted element
        $value->removeElement($productUnitPrecision);

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
                    [(new PriceAttributeProductPrice())->setProduct($product)->setUnit($unit)->setPrice($price)]
                ]
            );

        // prices for deleted product unit exist, violation should be added
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['product' => null, 'unit' => [$unit]])
            ->willReturn([new PriceAttributeProductPrice()]);

        $this->setRoot($form);
        $constraint = new PriceForProductUnitExists();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('%units%', 'item')
            ->assertRaised();
    }

    public function testValidationWithoutErrorsWhenEmptyPriceValue()
    {
        $unit = (new ProductUnit())->setCode('item');
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($unit);
        $elements = [$productUnitPrecision];
        $product = (new Product())->setPrimaryUnitPrecision($productUnitPrecision);
        $value = $this->createPersistentCollectionForUnitPrecisions($elements);
        $price = (new Price())->setValue(null);

        // remove precision
        // now collection has deleted element
        $value->removeElement($productUnitPrecision);

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
                    [(new PriceAttributeProductPrice())->setProduct($product)->setUnit($unit)->setPrice($price)]
                ]
            );

        // prices for deleted product unit not exist, violation shouldn't be added
        $this->repository->expects($this->never())
            ->method('findBy')
            ->with(['product' => null, 'unit' => [$unit]])
            ->willReturn([]);

        $this->setRoot($form);
        $constraint = new PriceForProductUnitExists();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidationWithoutErrorsWhenHaveNoPricesInDb()
    {
        $unit = (new ProductUnit())->setCode('item');
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($unit);
        $elements = [$productUnitPrecision];
        $product = (new Product())->setPrimaryUnitPrecision($productUnitPrecision);
        $value = $this->createPersistentCollectionForUnitPrecisions($elements);
        $price = (new Price())->setValue('22');

        // remove precision
        // now collection has deleted element
        $value->removeElement($productUnitPrecision);

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
                    [(new PriceAttributeProductPrice())->setProduct($product)->setUnit($unit)->setPrice($price)]
                ]
            );

        // prices for deleted product unit not exist, violation shouldn't be added
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['product' => null, 'unit' => [$unit]])
            ->willReturn([]);

        $this->setRoot($form);
        $constraint = new PriceForProductUnitExists();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    private function createPersistentCollectionForUnitPrecisions(array $elements): PersistentCollection
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $collection = new ArrayCollection($elements);
        $value = new PersistentCollection($em, $classMetadata, $collection);
        $value->takeSnapshot();

        return $value;
    }
}
