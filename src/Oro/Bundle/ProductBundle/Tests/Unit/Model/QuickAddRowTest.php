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
        self::assertEquals(self::INDEX, $row->getEntityIdentifier());
        self::assertEquals(self::SKU, $row->getSku());
        self::assertEquals(self::QUANTITY, $row->getQuantity());
        self::assertEquals(self::UNIT, $row->getUnit());
    }

    public function testProductGetterSetter(): void
    {
        $product = (new Product())
            ->setSku(self::SKU);

        $row = new QuickAddRow(self::INDEX, self::SKU, 0, self::UNIT);
        $row->setProduct($product);

        self::assertEquals($product, $row->getProduct());
        self::assertEquals(self::SKU, $row->getProductSku());
    }

    public function testAddError(): void
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
