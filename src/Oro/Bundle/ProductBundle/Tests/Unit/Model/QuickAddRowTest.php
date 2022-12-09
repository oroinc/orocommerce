<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;

class QuickAddRowTest extends \PHPUnit\Framework\TestCase
{
    private const INDEX = 1;
    private const SKU = 'SKU1';
    private const QUANTITY = 1.00;
    private const UNIT = 'item';

    public function testConstruct(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, self::QUANTITY, self::UNIT);
        self::assertEquals(self::INDEX, $row->getIndex());
        self::assertEquals(self::SKU, $row->getSku());
        self::assertEquals(self::QUANTITY, $row->getQuantity());
        self::assertEquals(self::UNIT, $row->getUnit());
        self::assertFalse($row->isValid());
    }

    public function testProductGetterSetter(): void
    {
        $product = new Product();

        $row = new QuickAddRow(self::INDEX, self::SKU, 0, self::UNIT);
        $row->setProduct($product);

        self::assertEquals($product, $row->getProduct());
    }

    public function testSetValid(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, 0, self::UNIT);
        $row->setValid(true);
        self::assertTrue($row->isValid());
    }

    public function testAddErrorWithoutPropertyPath(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, self::QUANTITY, self::UNIT);

        $message = 'sample message';
        $additionalParameters = ['sample_key' => 'sample_value'];
        $row->addError($message, $additionalParameters);

        self::assertEquals(
            [
                [
                    'message' => $message,
                    'parameters' => array_merge(
                        $additionalParameters,
                        ['{{ sku }}' => $row->getSku(), '{{ index }}' => $row->getIndex()]
                    ),
                    'propertyPath' => '',
                ],
            ],
            $row->getErrors()
        );
    }

    public function testAddErrorWithPropertyPath(): void
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, self::QUANTITY, self::UNIT);

        $message = 'sample message';
        $additionalParameters = ['sample_key' => 'sample_value'];
        $propertyPath = 'samplePath';
        $row->addError($message, $additionalParameters, $propertyPath);

        self::assertEquals(
            [
                [
                    'message' => $message,
                    'parameters' => array_merge(
                        $additionalParameters,
                        ['{{ sku }}' => $row->getSku(), '{{ index }}' => $row->getIndex()]
                    ),
                    'propertyPath' => $propertyPath,
                ],
            ],
            $row->getErrors()
        );
    }
}
