<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Model\ProductRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class QuickAddRowCollectionTest extends \PHPUnit\Framework\TestCase
{
    const SKU1 = 'SKU1Абв';
    const SKU1_UPPER = 'SKU1АБВ';
    const SKU2 = 'SKU2';
    const SKU3 = 'SKU3';

    const QUANTITY1 = 1;
    const QUANTITY2 = 2.5;

    const UNIT1 = 'item';

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
    }

    public function testToString()
    {
        $collection = new QuickAddRowCollection();
        $this->assertEquals('', (string) $collection);

        $this->addTwoCompleteRows($collection);
        $this->assertEquals(implode(PHP_EOL, ['SKU1Абв, 1', 'SKU2, 2.5']), (string) $collection);

        $this->addIncompleteRow($collection);
        $this->assertEquals(implode(PHP_EOL, ['SKU1Абв, 1', 'SKU2, 2.5', 'SKU3, ']), (string) $collection);
    }

    public function testGetValidRows()
    {
        $collection = new QuickAddRowCollection();

        $this->addTwoCompleteRows($collection);
        $this->assertCount(0, $collection->getValidRows());

        $this->addTwoValidRows($collection);
        $this->assertCount(2, $collection->getValidRows());
        $this->assertIsSku1Row($collection->getValidRows()->first());
    }

    public function testGetInvalidRows()
    {
        $collection = new QuickAddRowCollection();

        $this->addTwoCompleteRows($collection);
        $this->assertCount(2, $collection->getInvalidRows());
        $this->assertIsSku1Row($collection->getInvalidRows()->first());
    }

    public function testMapAndGetProducts()
    {
        $collection = new QuickAddRowCollection();
        $this->addTwoCompleteRows($collection);

        $this->assertCount(0, $collection->getProducts());

        $validProduct = [self::SKU1_UPPER =>  (new Product())->setSku(self::SKU1)];
        $invalidProduct = [self::SKU3 =>  (new Product())->setSku(self::SKU3)];

        $collection->mapProducts(array_merge($validProduct, $invalidProduct));
        $this->assertEquals($validProduct, $collection->getProducts());
    }

    public function testGetSkus()
    {
        $collection = new QuickAddRowCollection();
        $this->addTwoCompleteRows($collection);

        $this->assertEquals([self::SKU1, self::SKU2], $collection->getSkus());
    }

    public function testGetFormData()
    {
        $productRow = new ProductRow();
        $productRow->productSku = self::SKU1;
        $productRow->productQuantity = self::QUANTITY1;
        $productRow->productUnit = self::UNIT1;

        $productRow2 = new ProductRow();
        $productRow2->productSku = self::SKU2;
        $productRow2->productQuantity = self::QUANTITY2;
        $productRow2->productUnit = self::UNIT1;

        $expectedFormData = [
            QuickAddType::PRODUCTS_FIELD_NAME => [
                $productRow,
                $productRow2
            ]
        ];

        $collection = new QuickAddRowCollection();
        $this->addTwoValidRows($collection);

        $this->assertEquals($expectedFormData, $collection->getFormData());
    }

    private function addTwoCompleteRows(QuickAddRowCollection $collection)
    {
        $collection->add(new QuickAddRow(1, self::SKU1, self::QUANTITY1, self::UNIT1));
        $collection->add(new QuickAddRow(2, self::SKU2, self::QUANTITY2, self::UNIT1));
    }

    private function addTwoValidRows(QuickAddRowCollection $collection)
    {
        $row1 = new QuickAddRow(1, self::SKU1, self::QUANTITY1, self::UNIT1);
        $row1->setValid(true);

        $row2 = new QuickAddRow(2, self::SKU2, self::QUANTITY2, self::UNIT1);
        $row2->setValid(true);

        $collection->add($row1);
        $collection->add($row2);
    }

    private function addIncompleteRow(QuickAddRowCollection $collection)
    {
        $collection->add(new QuickAddRow(3, self::SKU3, null));
    }

    private function assertIsSku1Row(QuickAddRow $row)
    {
        $this->assertEquals(self::SKU1, $row->getSku());
        $this->assertEquals(self::QUANTITY1, $row->getQuantity());
    }
}
