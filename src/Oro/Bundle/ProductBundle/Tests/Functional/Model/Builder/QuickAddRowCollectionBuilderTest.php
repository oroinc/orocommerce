<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Model\Builder;

use Box\Spout\Common\Exception\UnsupportedTypeException;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class QuickAddRowCollectionBuilderTest extends WebTestCase
{
    private QuickAddRowCollectionBuilder $quickAddRowCollectionBuilder;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadProductData::class]);

        $this->quickAddRowCollectionBuilder = self::getContainer()
            ->get('oro_product.model.builder.quick_add_row_collection');
    }

    private function createUploadedFile(string $fileName): UploadedFile
    {
        return new UploadedFile(__DIR__ . '/data/' . $fileName, $fileName);
    }

    private function assertQuickAddRow(
        QuickAddRow $row,
        int $expectedIndex,
        string $expectedSku,
        float $expectedQuantity,
        string $expectedUnit,
        ?string $expectedProduct
    ): void {
        $message = sprintf('Expected sku: %s. Expected index: %d.', $expectedSku, $expectedIndex);
        self::assertSame($expectedIndex, $row->getIndex(), 'Index. ' . $message);
        self::assertSame($expectedSku, $row->getSku(), 'Sku. ' . $message);
        self::assertSame($expectedQuantity, $row->getQuantity(), 'Quantity. ' . $message);
        self::assertSame($expectedUnit, $row->getUnit(), 'Unit. ' . $message);
        if (null === $expectedProduct) {
            self::assertTrue(null === $row->getProduct(), 'Product. ' . $message);
        } else {
            self::assertSame($this->getReference($expectedProduct), $row->getProduct(), 'Product. ' . $message);
        }
    }

    public function testBuildFromArray(): void
    {
        $data = [
            ['index' => 0, 'sku' => 'product-1', 'quantity' => '1', 'unit' => 'item'],
            ['sku' => 'SKIP1', 'unit' => 'item'],
            ['index' => 2, 'sku' => 'продукт-7', 'quantity' => '2', 'unit' => 'item'],
            ['index' => 3, 'quantity' => '1', 'unit' => 'item'],
            ['index' => 10, 'sku' => 'продукт-9', 'quantity' => '3', 'unit' => 'item'],
        ];

        $collection = $this->quickAddRowCollectionBuilder->buildFromArray($data);

        self::assertCount(5, $collection);
        $this->assertQuickAddRow($collection->get(0), 0, 'product-1', 1.0, 'item', LoadProductData::PRODUCT_1);
        $this->assertQuickAddRow($collection->get(1), 1, 'SKIP1', 0.0, 'item', null);
        $this->assertQuickAddRow($collection->get(2), 2, 'продукт-7', 2.0, 'item', LoadProductData::PRODUCT_7);
        $this->assertQuickAddRow($collection->get(3), 3, '', 1.0, 'item', null);
        $this->assertQuickAddRow($collection->get(4), 10, 'продукт-9', 3.0, 'item', null);
    }

    /**
     * @dataProvider delimiterDataProvider
     */
    public function testBuildFromCopyPasteText(string $delimiter): void
    {
        $text = <<<TEXT
product-1${delimiter}1${delimiter}item
product-2${delimiter}2
TEXT;

        $collection = $this->quickAddRowCollectionBuilder->buildFromCopyPasteText($text);

        self::assertCount(2, $collection);
        $this->assertQuickAddRow($collection->get(0), 1, 'product-1', 1.0, 'item', LoadProductData::PRODUCT_1);
        $this->assertQuickAddRow($collection->get(1), 2, 'product-2', 2.0, 'milliliter', LoadProductData::PRODUCT_2);
    }

    /**
     * @dataProvider delimiterDataProvider
     */
    public function testBuildFromCopyPasteTextWithOrganization(string $delimiter): void
    {
        $text = <<<TEXT
"product-1, Organization"${delimiter}1${delimiter}item
"product-2,2nd Organization"${delimiter}2${delimiter}item
"product-3"${delimiter}3${delimiter}item
TEXT;

        $collection = $this->quickAddRowCollectionBuilder->buildFromCopyPasteText($text);

        self::assertCount(3, $collection);

        $this->assertQuickAddRow($collection->get(0), 1, 'product-1', 1.0, 'item', LoadProductData::PRODUCT_1);
        self::assertEquals('Organization', $collection->get(0)->getOrganization());

        $this->assertQuickAddRow($collection->get(1), 2, 'product-2', 2.0, 'item', LoadProductData::PRODUCT_2);
        self::assertEquals('2nd Organization', $collection->get(1)->getOrganization());

        $this->assertQuickAddRow($collection->get(2), 3, 'product-3', 3.0, 'item', LoadProductData::PRODUCT_3);
        self::assertNull($collection->get(2)->getOrganization());
    }

    /**
     * @dataProvider delimiterDataProvider
     */
    public function testBuildFromCopyPasteTextWithSameSkuInDifferentLines(string $delimiter): void
    {
        $text = <<<TEXT
product-1${delimiter}1${delimiter}item
product-2${delimiter}2${delimiter}item
Product-1${delimiter}1${delimiter}set
PRODUCT-1${delimiter}1
TEXT;

        $collection = $this->quickAddRowCollectionBuilder->buildFromCopyPasteText($text);

        self::assertCount(4, $collection);
        $this->assertQuickAddRow($collection->get(0), 1, 'product-1', 1.0, 'item', LoadProductData::PRODUCT_1);
        $this->assertQuickAddRow($collection->get(1), 2, 'product-2', 2.0, 'item', LoadProductData::PRODUCT_2);
        $this->assertQuickAddRow($collection->get(2), 3, 'Product-1', 1.0, 'set', LoadProductData::PRODUCT_1);
        $this->assertQuickAddRow($collection->get(3), 4, 'PRODUCT-1', 1.0, 'milliliter', LoadProductData::PRODUCT_1);
    }

    public function delimiterDataProvider(): array
    {
        return [
            [' '],
            [','],
            [';'],
            ["\t"],
        ];
    }

    /**
     * @dataProvider uploadedFileProvider
     */
    public function testBuildFromFile(string $fileName): void
    {
        $file = $this->createUploadedFile($fileName);
        $collection = $this->quickAddRowCollectionBuilder->buildFromFile($file);

        self::assertCount(2, $collection);
        $this->assertQuickAddRow($collection->get(0), 1, 'product-1', 1.0, 'item', LoadProductData::PRODUCT_1);
        $this->assertQuickAddRow($collection->get(1), 2, 'product-2', 2.0, 'item', LoadProductData::PRODUCT_2);
    }

    public function uploadedFileProvider(): array
    {
        return [
            ['quick-order.csv'],
            ['quick-order.ods'],
            ['quick-order.xlsx'],
        ];
    }

    public function testBuildFromFileFailsBecauseOfWrongFormat(): void
    {
        $file = $this->createUploadedFile('quick-order.odt');

        $this->expectException(UnsupportedTypeException::class);

        $this->quickAddRowCollectionBuilder->buildFromFile($file);
    }
}
