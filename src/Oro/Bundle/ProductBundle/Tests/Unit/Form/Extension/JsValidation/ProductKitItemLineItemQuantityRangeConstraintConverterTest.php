<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension\JsValidation;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Form\Extension\JsValidation\ProductKitItemLineItemQuantityRangeConstraintConverter;
use Oro\Bundle\ProductBundle\Model\ProductKitItemAwareInterface;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemLineItemQuantityRange;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ProductKitItemLineItemQuantityRangeConstraintConverterTest extends TestCase
{
    private ProductKitItemLineItemQuantityRangeConstraintConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->converter = new ProductKitItemLineItemQuantityRangeConstraintConverter();
    }

    public function testSupportsForProductKitItemLineItemQuantityRange(): void
    {
        self::assertTrue($this->converter->supports(new ProductKitItemLineItemQuantityRange()));
    }

    public function testSupportsForNotProductKitItemLineItemQuantityRange(): void
    {
        self::assertFalse($this->converter->supports(new NotBlank()));
    }

    public function testProductKitItemLineItemQuantityRangeConstraintWhenNoForm(): void
    {
        $constraint = new ProductKitItemLineItemQuantityRange();

        self::assertNull($this->converter->convertConstraint($constraint));
    }

    public function testProductKitItemLineItemQuantityRangeConstraintWhenNoParentForm(): void
    {
        $constraint = new ProductKitItemLineItemQuantityRange();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getParent')
            ->willReturn(null);

        self::assertNull($this->converter->convertConstraint($constraint, $form));
    }

    public function testProductKitItemLineItemQuantityRangeConstraintWhenNoParentFormData(): void
    {
        $constraint = new ProductKitItemLineItemQuantityRange();
        $form = $this->createMock(FormInterface::class);
        $parentForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getParent')
            ->willReturn($parentForm);
        $parentForm->expects(self::once())
            ->method('getData')
            ->willReturn(null);

        self::assertNull($this->converter->convertConstraint($constraint, $form));
    }

    public function testProductKitItemLineItemQuantityRangeConstraintWhenNoKitItem(): void
    {
        $constraint = new ProductKitItemLineItemQuantityRange();
        $form = $this->createMock(FormInterface::class);
        $parentForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getParent')
            ->willReturn($parentForm);
        $parentFormData = $this->createMock(ProductKitItemAwareInterface::class);
        $parentForm->expects(self::once())
            ->method('getData')
            ->willReturn($parentFormData);
        $parentFormData->expects(self::once())
            ->method('getKitItem')
            ->willReturn(null);

        self::assertNull($this->converter->convertConstraint($constraint, $form));
    }

    public function testProductKitItemLineItemQuantityRangeConstraintWhenKitItemDoestNotHaveMinAndMaxQuantities(): void
    {
        $constraint = new ProductKitItemLineItemQuantityRange([
            'minMessage' => 'min msg'
        ]);
        $form = $this->createMock(FormInterface::class);
        $parentForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getParent')
            ->willReturn($parentForm);
        $parentFormData = $this->createMock(ProductKitItemAwareInterface::class);
        $parentForm->expects(self::once())
            ->method('getData')
            ->willReturn($parentFormData);
        $kitItem = $this->createMock(ProductKitItem::class);
        $parentFormData->expects(self::once())
            ->method('getKitItem')
            ->willReturn($kitItem);
        $kitItem->expects(self::once())
            ->method('getMinimumQuantity')
            ->willReturn(null);
        $kitItem->expects(self::once())
            ->method('getMaximumQuantity')
            ->willReturn(null);

        self::assertNull($this->converter->convertConstraint($constraint, $form));
    }

    public function testProductKitItemLineItemQuantityRangeConstraintWithMinMessage(): void
    {
        $constraint = new ProductKitItemLineItemQuantityRange([
            'minMessage' => 'min msg'
        ]);
        $form = $this->createMock(FormInterface::class);
        $parentForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getParent')
            ->willReturn($parentForm);
        $parentFormData = $this->createMock(ProductKitItemAwareInterface::class);
        $parentForm->expects(self::once())
            ->method('getData')
            ->willReturn($parentFormData);
        $kitItem = $this->createMock(ProductKitItem::class);
        $parentFormData->expects(self::once())
            ->method('getKitItem')
            ->willReturn($kitItem);
        $kitItem->expects(self::once())
            ->method('getMinimumQuantity')
            ->willReturn(1.0);
        $kitItem->expects(self::once())
            ->method('getMaximumQuantity')
            ->willReturn(null);

        $expectedConstraint = new Range(['min' => 1.0, 'minMessage' => $constraint->minMessage]);
        self::assertEquals($expectedConstraint, $this->converter->convertConstraint($constraint, $form));
    }

    public function testProductKitItemLineItemQuantityRangeConstraintWithMaxMessage(): void
    {
        $constraint = new ProductKitItemLineItemQuantityRange([
            'maxMessage' => 'max msg'
        ]);
        $form = $this->createMock(FormInterface::class);
        $parentForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getParent')
            ->willReturn($parentForm);
        $parentFormData = $this->createMock(ProductKitItemAwareInterface::class);
        $parentForm->expects(self::once())
            ->method('getData')
            ->willReturn($parentFormData);
        $kitItem = $this->createMock(ProductKitItem::class);
        $parentFormData->expects(self::once())
            ->method('getKitItem')
            ->willReturn($kitItem);
        $kitItem->expects(self::once())
            ->method('getMinimumQuantity')
            ->willReturn(null);
        $kitItem->expects(self::once())
            ->method('getMaximumQuantity')
            ->willReturn(10.0);

        $expectedConstraint = new Range(['max' => 10.0, 'maxMessage' => $constraint->maxMessage]);
        self::assertEquals($expectedConstraint, $this->converter->convertConstraint($constraint, $form));
    }

    public function testProductKitItemLineItemQuantityRangeConstraintWithNotInRangeMessage(): void
    {
        $constraint = new ProductKitItemLineItemQuantityRange([
            'notInRangeMessage' => 'not in range msg'
        ]);
        $form = $this->createMock(FormInterface::class);
        $parentForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getParent')
            ->willReturn($parentForm);
        $parentFormData = $this->createMock(ProductKitItemAwareInterface::class);
        $parentForm->expects(self::once())
            ->method('getData')
            ->willReturn($parentFormData);
        $kitItem = $this->createMock(ProductKitItem::class);
        $parentFormData->expects(self::once())
            ->method('getKitItem')
            ->willReturn($kitItem);
        $kitItem->expects(self::once())
            ->method('getMinimumQuantity')
            ->willReturn(1.0);
        $kitItem->expects(self::once())
            ->method('getMaximumQuantity')
            ->willReturn(10.0);

        $expectedConstraint = new Range([
            'min' => 1.0,
            'max' => 10.0,
            'notInRangeMessage' => $constraint->notInRangeMessage
        ]);
        self::assertEquals($expectedConstraint, $this->converter->convertConstraint($constraint, $form));
    }
}
