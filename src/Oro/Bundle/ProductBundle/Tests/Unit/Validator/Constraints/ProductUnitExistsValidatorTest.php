<?php

namespace Oro\Bundle\ProductBundle\Tests\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductUnitExists;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductUnitExistsValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductUnitExistsValidatorTest extends ConstraintValidatorTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->constraint = new ProductUnitExists();

        $this->context = $this->createContext();
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);
    }

    public function testUnitExistsForProduct()
    {
        $sku = 'SKU1';
        $quickAddRow = new QuickAddRow(1, $sku, 3, 'item');

        $product = $this->createMock(Product::class);
        $product->method('getAvailableUnitCodes')
            ->willReturn(['item', 'set']);

        $quickAddRow->setProduct($product);

        $this->validator->validate($quickAddRow, $this->constraint);

        $this->assertNoViolation();
    }

    public function testUnitDoesNotExistForProduct()
    {
        $sku = 'SKU1';
        $quickAddRow = new QuickAddRow(1, $sku, 3, 'item');

        $product = $this->createMock(Product::class);
        $product->method('getAvailableUnitCodes')
            ->willReturn(['set']);
        $product->method('getSku')
            ->willReturn($sku);

        $quickAddRow->setProduct($product);

        $this->validator->validate($quickAddRow, $this->constraint);

        $this->buildViolation('oro.product.frontend.quick_add.validation.invalid_unit')
            ->setParameter('{{ sku }}', 'SKU1')
            ->setParameter('{{ unit }}', 'item')
            ->assertRaised();
    }

    /**
     * @return ProductUnitExistsValidator
     */
    protected function createValidator()
    {
        return new ProductUnitExistsValidator();
    }
}
