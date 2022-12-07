<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;

class QuickAddRowTest extends \PHPUnit\Framework\TestCase
{
    const INDEX = 1;
    const SKU = 'SKU1';
    const QUANTITY = 1.00;
    const UNIT = 'item';

    public function testConstruct()
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, self::QUANTITY, self::UNIT);
        $this->assertEquals(self::INDEX, $row->getIndex());
        $this->assertEquals(self::SKU, $row->getSku());
        $this->assertEquals(self::QUANTITY, $row->getQuantity());
        $this->assertEquals(self::UNIT, $row->getUnit());
        $this->assertFalse($row->isValid());
    }

    public function testProductGetterSetter()
    {
        $product = new Product();

        $row = new QuickAddRow(self::INDEX, self::SKU, null, self::UNIT);
        $row->setProduct($product);

        $this->assertEquals($product, $row->getProduct());
    }

    public function testSetValid()
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, null, self::UNIT);
        $row->setValid(true);
        $this->assertTrue($row->isValid());
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
