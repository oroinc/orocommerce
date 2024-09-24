<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\InventoryBundle\Validator\Constraints\HasEnoughInventoryLevel;
use Oro\Bundle\InventoryBundle\Validator\Constraints\HasEnoughInventoryLevelValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class HasEnoughInventoryLevelValidatorTest extends ConstraintValidatorTestCase
{
    private ManagerRegistry|MockObject $managerRegistry;

    private InventoryQuantityManager|MockObject $quantityManager;

    private UnitLabelFormatterInterface|MockObject $unitLabelFormatter;

    private InventoryLevelRepository|MockObject $inventoryLevelRepo;

    #[\Override]
    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->quantityManager = $this->createMock(InventoryQuantityManager::class);
        $this->unitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);

        $this->inventoryLevelRepo = $this->createMock(InventoryLevelRepository::class);
        $this->managerRegistry
            ->method('getRepository')
            ->with(InventoryLevel::class)
            ->willReturn($this->inventoryLevelRepo);

        $this->unitLabelFormatter
            ->method('format')
            ->willReturnCallback(static fn (string $unitCode) => $unitCode . ' (formatted)');

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): HasEnoughInventoryLevelValidator
    {
        return new HasEnoughInventoryLevelValidator(
            $this->managerRegistry,
            $this->quantityManager,
            $this->unitLabelFormatter
        );
    }

    public function testValidateWhenNull(): void
    {
        $this->validator->validate(null, new HasEnoughInventoryLevel());

        $this->assertNoViolation();
    }

    public function testValidateWhenNoProduct(): void
    {
        $lineItem = new ProductLineItemStub(42);
        $this->validator->validate($lineItem, new HasEnoughInventoryLevel());

        $this->assertNoViolation();
    }

    public function testValidateWhenShouldNotDecrement(): void
    {
        $product = new Product();
        $lineItem = (new ProductLineItemStub(42))
            ->setProduct($product);

        $this->quantityManager
            ->expects(self::once())
            ->method('shouldDecrement')
            ->with($product)
            ->willReturn(false);

        $this->validator->validate($lineItem, new HasEnoughInventoryLevel());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider productUnitDataProvider
     */
    public function testValidateWhenEnoughQuantity(
        ?ProductUnit $productUnit,
        ProductUnit $expectedProductUnit
    ): void {
        $productUnitEach = (new ProductUnit())->setCode('each');
        $product = (new Product())
            ->setSku('sample-sku')
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($productUnitEach));
        $lineItem = (new ProductLineItemStub(42))
            ->setQuantity(12.3456)
            ->setProduct($product);

        ReflectionUtil::setPropertyValue($lineItem, 'unit', $productUnit);

        $this->quantityManager
            ->expects(self::once())
            ->method('shouldDecrement')
            ->with($product)
            ->willReturn(true);

        $inventoryLevel = $this->createMock(InventoryLevel::class);
        $this->inventoryLevelRepo
            ->expects(self::once())
            ->method('getLevelByProductAndProductUnit')
            ->with($product, $expectedProductUnit)
            ->willReturn($inventoryLevel);

        $this->quantityManager
            ->expects(self::once())
            ->method('hasEnoughQuantity')
            ->with($inventoryLevel, $lineItem->getQuantity())
            ->willReturn(true);

        $constraint = new HasEnoughInventoryLevel();
        $this->validator->validate($lineItem, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider productUnitDataProvider
     */
    public function testValidateWhenNoInventoryLevel(
        ?ProductUnit $productUnit,
        ProductUnit $expectedProductUnit
    ): void {
        $productUnitEach = (new ProductUnit())->setCode('each');
        $product = (new Product())
            ->setSku('sample-sku')
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($productUnitEach));
        $lineItem = (new ProductLineItemStub(42))
            ->setQuantity(12.3456)
            ->setProduct($product);

        ReflectionUtil::setPropertyValue($lineItem, 'unit', $productUnit);

        $this->quantityManager
            ->expects(self::once())
            ->method('shouldDecrement')
            ->with($product)
            ->willReturn(true);

        $this->inventoryLevelRepo
            ->expects(self::once())
            ->method('getLevelByProductAndProductUnit')
            ->with($product, $expectedProductUnit)
            ->willReturn(null);

        $constraint = new HasEnoughInventoryLevel();
        $this->validator->validate($lineItem, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('{{ product_sku }}', '"' . $product->getSku() . '"')
            ->setParameter('{{ unit }}', '"' . $expectedProductUnit->getCode() . ' (formatted)"')
            ->setParameter('{{ quantity }}', $lineItem->getQuantity())
            ->atPath('property.path.quantity')
            ->setCause($lineItem)
            ->setCode(HasEnoughInventoryLevel::NOT_ENOUGH_QUANTITY)
            ->assertRaised();
    }

    public function productUnitDataProvider(): iterable
    {
        $productUnitItem = (new ProductUnit())->setCode('item');

        yield 'product has unit' => [$productUnitItem, $productUnitItem];
        yield 'product has no unit, falling back to primary unit' => [null, (new ProductUnit())->setCode('each')];
    }

    /**
     * @dataProvider productUnitDataProvider
     */
    public function testValidateWhenNotEnoughQuantity(
        ?ProductUnit $productUnit,
        ProductUnit $expectedProductUnit
    ): void {
        $productUnitEach = (new ProductUnit())->setCode('each');
        $product = (new Product())
            ->setSku('sample-sku')
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($productUnitEach));
        $lineItem = (new ProductLineItemStub(42))
            ->setQuantity(12.3456)
            ->setProduct($product);

        ReflectionUtil::setPropertyValue($lineItem, 'unit', $productUnit);

        $this->quantityManager
            ->expects(self::once())
            ->method('shouldDecrement')
            ->with($product)
            ->willReturn(true);

        $inventoryLevel = $this->createMock(InventoryLevel::class);
        $this->inventoryLevelRepo
            ->expects(self::once())
            ->method('getLevelByProductAndProductUnit')
            ->with($product, $expectedProductUnit)
            ->willReturn($inventoryLevel);

        $this->quantityManager
            ->expects(self::once())
            ->method('hasEnoughQuantity')
            ->with($inventoryLevel, $lineItem->getQuantity())
            ->willReturn(false);

        $constraint = new HasEnoughInventoryLevel();
        $this->validator->validate($lineItem, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('{{ product_sku }}', '"' . $product->getSku() . '"')
            ->setParameter('{{ unit }}', '"' . $expectedProductUnit->getCode() . ' (formatted)"')
            ->setParameter('{{ quantity }}', $lineItem->getQuantity())
            ->atPath('property.path.quantity')
            ->setCause($lineItem)
            ->setCode(HasEnoughInventoryLevel::NOT_ENOUGH_QUANTITY)
            ->assertRaised();
    }
}
