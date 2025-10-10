<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Model\QuickAddField;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuickAddRowTest extends \PHPUnit\Framework\TestCase
{
    private const INDEX = 1;
    private const SKU = 'SKU1';
    private const QUANTITY = 1.0;
    private const UNIT = 'item';
    private const ORGANIZATION = 'org';

    public function testConstructWithRequiredParameters(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, self::QUANTITY);
        self::assertEquals(self::INDEX, $row->getIndex());
        self::assertEquals(self::INDEX, $row->getEntityIdentifier());
        self::assertEquals(self::SKU, $row->getSku());
        self::assertEquals(self::QUANTITY, $row->getQuantity());
        self::assertNull($row->getUnit());
        self::assertNull($row->getOrganization());
    }

    public function testConstructWithAllParameters(): void
    {
        $row = new QuickAddRow(
            self::INDEX,
            self::SKU,
            self::QUANTITY,
            self::UNIT,
            self::ORGANIZATION
        );
        self::assertEquals(self::INDEX, $row->getIndex());
        self::assertEquals(self::INDEX, $row->getEntityIdentifier());
        self::assertEquals(self::SKU, $row->getSku());
        self::assertEquals(self::QUANTITY, $row->getQuantity());
        self::assertEquals(self::UNIT, $row->getUnit());
        self::assertEquals(self::ORGANIZATION, $row->getOrganization());
    }

    public function testQuantityGetterSetter(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, 0.0);
        self::assertEquals(0.0, $row->getQuantity());

        $row->setQuantity(self::QUANTITY);
        self::assertEquals(self::QUANTITY, $row->getQuantity());
    }

    public function testProductGetterSetter(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, 0.0);
        self::assertNull($row->getProduct());
        self::assertNull($row->getProductSku());

        $product = new Product();
        $product->setSku(self::SKU);
        $row->setProduct($product);
        self::assertSame($product, $row->getProduct());
        self::assertEquals(self::SKU, $row->getProductSku());
    }

    public function testUnitGetterSetter(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, 0.0);
        self::assertNull($row->getUnit());

        $row->setUnit(self::UNIT);
        self::assertEquals(self::UNIT, $row->getUnit());
    }

    public function testOrganizationGetterSetter(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, 0.0);
        self::assertNull($row->getOrganization());

        $row->setOrganization(self::ORGANIZATION);
        self::assertEquals(self::ORGANIZATION, $row->getOrganization());
    }

    public function testErrorCollection(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, self::QUANTITY);
        self::assertFalse($row->hasErrors());
        self::assertSame([], $row->getErrors());

        $row->addError('message 1');
        $row->addError('message 2', ['{{ key }}' => 'value'], 'somePath');
        self::assertEquals(
            [
                [
                    'message' => 'message 1',
                    'parameters' => ['{{ sku }}' => $row->getSku(), '{{ index }}' => $row->getIndex()],
                    'propertyPath' => ''
                ],
                [
                    'message' => 'message 2',
                    'parameters' => [
                        '{{ key }}' => 'value',
                        '{{ sku }}' => $row->getSku(),
                        '{{ index }}' => $row->getIndex()
                    ],
                    'propertyPath' => 'somePath'
                ]
            ],
            $row->getErrors()
        );

        $row->addError(
            'message 3',
            ['{{ key }}' => 'value', '{{ sku }}' => 'SKU10', '{{ index }}' => 10],
            'somePath'
        );
        self::assertEquals(
            [
                [
                    'message' => 'message 1',
                    'parameters' => ['{{ sku }}' => $row->getSku(), '{{ index }}' => $row->getIndex()],
                    'propertyPath' => ''
                ],
                [
                    'message' => 'message 2',
                    'parameters' => [
                        '{{ key }}' => 'value',
                        '{{ sku }}' => $row->getSku(),
                        '{{ index }}' => $row->getIndex()
                    ],
                    'propertyPath' => 'somePath'
                ],
                [
                    'message' => 'message 3',
                    'parameters' => [
                        '{{ key }}' => 'value',
                        '{{ sku }}' => $row->getSku(),
                        '{{ index }}' => $row->getIndex()
                    ],
                    'propertyPath' => 'somePath'
                ]
            ],
            $row->getErrors()
        );
    }

    public function testWarningsCollection(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, self::QUANTITY);
        self::assertSame([], $row->getWarnings());

        $row->addWarning('message 1');
        $row->addWarning('message 2', ['{{ key }}' => 'value'], 'somePath');
        self::assertEquals(
            [
                [
                    'message' => 'message 1',
                    'parameters' => ['{{ sku }}' => $row->getSku(), '{{ index }}' => $row->getIndex()],
                    'propertyPath' => ''
                ],
                [
                    'message' => 'message 2',
                    'parameters' => [
                        '{{ key }}' => 'value',
                        '{{ sku }}' => $row->getSku(),
                        '{{ index }}' => $row->getIndex()
                    ],
                    'propertyPath' => 'somePath'
                ]
            ],
            $row->getWarnings()
        );

        $row->addWarning(
            'message 3',
            ['{{ key }}' => 'value', '{{ sku }}' => 'SKU10', '{{ index }}' => 10],
            'somePath'
        );
        self::assertEquals(
            [
                [
                    'message' => 'message 1',
                    'parameters' => ['{{ sku }}' => $row->getSku(), '{{ index }}' => $row->getIndex()],
                    'propertyPath' => ''
                ],
                [
                    'message' => 'message 2',
                    'parameters' => [
                        '{{ key }}' => 'value',
                        '{{ sku }}' => $row->getSku(),
                        '{{ index }}' => $row->getIndex()
                    ],
                    'propertyPath' => 'somePath'
                ],
                [
                    'message' => 'message 3',
                    'parameters' => [
                        '{{ key }}' => 'value',
                        '{{ sku }}' => $row->getSku(),
                        '{{ index }}' => $row->getIndex()
                    ],
                    'propertyPath' => 'somePath'
                ]
            ],
            $row->getWarnings()
        );
    }

    public function testAdditionalFieldsCollection(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, self::QUANTITY);
        self::assertSame([], $row->getAdditionalFields());
        self::assertNull($row->getAdditionalField('field'));

        $field = new QuickAddField('field', 'value');
        $anotherField = new QuickAddField('anotherField', 'value');
        $row->addAdditionalField($field);
        $row->addAdditionalField($anotherField);
        self::assertSame(['field' => $field, 'anotherField' => $anotherField], $row->getAdditionalFields());
        self::assertSame($field, $row->getAdditionalField('field'));
        self::assertSame($anotherField, $row->getAdditionalField('anotherField'));
        self::assertNull($row->getAdditionalField('unknownField'));
    }

    public function testGetProductHolder(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, self::QUANTITY);
        self::assertNull($row->getProductHolder());

        $product = new Product();
        $product->setSku(self::SKU);
        $row->setProduct($product);
        self::assertSame($product, $row->getProductHolder());
    }

    public function testGetProductUnit(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, self::QUANTITY);
        self::assertNull($row->getProductUnit());

        $product = new Product();
        $product->setSku(self::SKU);
        $row->setProduct($product);
        self::assertNull($row->getProductUnit());

        $row = new QuickAddRow(self::INDEX, self::SKU, self::QUANTITY, self::UNIT);
        self::assertNull($row->getProductUnit());

        $product = new Product();
        $product->setSku(self::SKU);
        $row->setProduct($product);
        $row->setUnit(self::UNIT);

        $productUnit = new ProductUnit();
        $productUnit->setCode(self::UNIT);

        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setUnit($productUnit);

        $product->addUnitPrecision($productUnitPrecision);

        self::assertSame($productUnit, $row->getProductUnit());
    }

    public function testGetProductUnitCode(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, self::QUANTITY);
        self::assertNull($row->getProductUnitCode());

        $row->setUnit(self::UNIT);
        self::assertEquals(self::UNIT, $row->getProductUnitCode());

        $row->setUnit(null);
        self::assertNull($row->getProductUnitCode());
    }
}
