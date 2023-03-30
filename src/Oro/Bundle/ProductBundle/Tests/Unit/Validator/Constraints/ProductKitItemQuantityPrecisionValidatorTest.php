<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService;
use Oro\Bundle\ProductBundle\Service\ProductKitItemProductUnitChecker;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemQuantityPrecision;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemQuantityPrecisionValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductKitItemQuantityPrecisionValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ProductKitItemQuantityPrecisionValidator
    {
        $configManager = $this->createMock(ConfigManager::class);

        return new ProductKitItemQuantityPrecisionValidator(
            new ProductKitItemProductUnitChecker(),
            new QuantityRoundingService($configManager)
        );
    }

    public function testGetTargets(): void
    {
        $constraint = new ProductKitItemQuantityPrecision();

        self::assertEquals([Constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $constraint = new IsNull();

        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, ProductKitItemQuantityPrecision::class)
        );

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateUnsupportedClass(): void
    {
        $value = new \stdClass();

        $this->expectExceptionObject(new UnexpectedValueException($value, ProductKitItem::class));

        $constraint = new ProductKitItemQuantityPrecision();
        $this->validator->validate($value, $constraint);
    }

    public function testValidateDoesNothingWhenNoProductUnit(): void
    {
        $constraint = new ProductKitItemQuantityPrecision();
        $this->validator->validate(new ProductKitItemStub(42), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateDoesNothingWhenNoReferencedUnitPrecisions(): void
    {
        $kitItem = (new ProductKitItemStub(42))
            ->setProductUnit((new ProductUnit())->setCode('item'))
            ->setMinimumQuantity(12.34)
            ->setMaximumQuantity(34.567);

        $constraint = new ProductKitItemQuantityPrecision();
        $this->validator->validate($kitItem, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenInvalidBothQuantitiesPrecision(): void
    {
        $productUnit = (new ProductUnit())->setCode('item');
        $unitPrecision1 = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision(1);
        $unitPrecision2 = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision(2);

        $product1 = (new ProductStub())->setPrimaryUnitPrecision($unitPrecision1);
        $product2 = (new ProductStub())->setPrimaryUnitPrecision($unitPrecision2);
        $kitItem = (new ProductKitItemStub(42))
            ->setProductUnit($productUnit)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product2))
            ->setMinimumQuantity(12.34)
            ->setMaximumQuantity(34.567);

        $constraint = new ProductKitItemQuantityPrecision();
        $this->validator->validate($kitItem, $constraint);

        $this->buildViolation($constraint->minimumQuantityMessage)
            ->setParameter('{{ value }}', $kitItem->getMinimumQuantity())
            ->setParameter('{{ precision }}', 1)
            ->atPath('property.path.minimumQuantity')
            ->setCode(ProductKitItemQuantityPrecision::MINIMUM_QUANTITY_PRECISION_ERROR)
            ->buildNextViolation($constraint->maximumQuantityMessage)
            ->setParameter('{{ value }}', $kitItem->getMaximumQuantity())
            ->setParameter('{{ precision }}', 1)
            ->atPath('property.path.maximumQuantity')
            ->setCode(ProductKitItemQuantityPrecision::MAXIMUM_QUANTITY_PRECISION_ERROR)
            ->assertRaised();
    }

    /**
     * @dataProvider invalidQuantityPrecisionDataProvider
     *
     * @param float $invalidQuantity
     * @param float|null $validQuantity
     */
    public function testValidateWhenInvalidMinimumQuantityPrecision(
        float $invalidQuantity,
        ?float $validQuantity
    ): void {
        $productUnit = (new ProductUnit())->setCode('item');
        $unitPrecision1 = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision(1);
        $unitPrecision2 = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision(2);

        $product1 = (new ProductStub())->setPrimaryUnitPrecision($unitPrecision1);
        $product2 = (new ProductStub())->setPrimaryUnitPrecision($unitPrecision2);
        $kitItem = (new ProductKitItemStub(42))
            ->setProductUnit($productUnit)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product2))
            ->setMinimumQuantity($invalidQuantity)
            ->setMaximumQuantity($validQuantity);

        $constraint = new ProductKitItemQuantityPrecision();
        $this->validator->validate($kitItem, $constraint);

        $this->buildViolation($constraint->minimumQuantityMessage)
            ->setParameter('{{ value }}', $kitItem->getMinimumQuantity())
            ->setParameter('{{ precision }}', 1)
            ->atPath('property.path.minimumQuantity')
            ->setCode(ProductKitItemQuantityPrecision::MINIMUM_QUANTITY_PRECISION_ERROR)
            ->assertRaised();
    }

    public function invalidQuantityPrecisionDataProvider(): array
    {
        return [
            ['invalidQuantity' => 12.34, 'validQuantity' => null],
            ['invalidQuantity' => 12.3456, 'validQuantity' => 0],
            ['invalidQuantity' => 12.3456, 'validQuantity' => 1.0],
            ['invalidQuantity' => 12.3456, 'validQuantity' => 1.1],
        ];
    }

    /**
     * @dataProvider invalidQuantityPrecisionDataProvider
     *
     * @param float $invalidQuantity
     * @param float|null $validQuantity
     */
    public function testValidateWhenInvalidMaximumQuantityPrecision(
        float $invalidQuantity,
        ?float $validQuantity
    ): void {
        $productUnit = (new ProductUnit())->setCode('item');
        $unitPrecision1 = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision(1);
        $unitPrecision2 = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision(2);

        $product1 = (new ProductStub())->setPrimaryUnitPrecision($unitPrecision1);
        $product2 = (new ProductStub())->setPrimaryUnitPrecision($unitPrecision2);
        $kitItem = (new ProductKitItemStub(42))
            ->setProductUnit($productUnit)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product2))
            ->setMinimumQuantity($validQuantity)
            ->setMaximumQuantity($invalidQuantity);

        $constraint = new ProductKitItemQuantityPrecision();
        $this->validator->validate($kitItem, $constraint);

        $this->buildViolation($constraint->maximumQuantityMessage)
            ->setParameter('{{ value }}', $kitItem->getMaximumQuantity())
            ->setParameter('{{ precision }}', 1)
            ->atPath('property.path.maximumQuantity')
            ->setCode(ProductKitItemQuantityPrecision::MAXIMUM_QUANTITY_PRECISION_ERROR)
            ->assertRaised();
    }

    /**
     * @dataProvider validQuantityPrecisionDataProvider
     *
     * @param float|null $minimumQuantity
     * @param float|null $maximumQuantity
     */
    public function testValidateWhenValidQuantityPrecision(
        ?float $minimumQuantity,
        ?float $maximumQuantity
    ): void {
        $productUnit = (new ProductUnit())->setCode('item');
        $unitPrecision1 = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision(1);
        $unitPrecision2 = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision(2);

        $product1 = (new ProductStub())->setPrimaryUnitPrecision($unitPrecision1);
        $product2 = (new ProductStub())->setPrimaryUnitPrecision($unitPrecision2);
        $kitItem = (new ProductKitItemStub(42))
            ->setProductUnit($productUnit)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product2))
            ->setMinimumQuantity($minimumQuantity)
            ->setMaximumQuantity($maximumQuantity);

        $constraint = new ProductKitItemQuantityPrecision();
        $this->validator->validate($kitItem, $constraint);

        $this->assertNoViolation();
    }

    public function validQuantityPrecisionDataProvider(): array
    {
        return [
            ['minimumQuantity' => null, 'maximumQuantity' => null],
            ['minimumQuantity' => 0, 'maximumQuantity' => 0],
            ['minimumQuantity' => 1, 'maximumQuantity' => 2],
            ['minimumQuantity' => 1.1, 'maximumQuantity' => 2.2],
        ];
    }
}
