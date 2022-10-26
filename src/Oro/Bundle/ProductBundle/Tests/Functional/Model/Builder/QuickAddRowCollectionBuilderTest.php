<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Model\Builder;

use Box\Spout\Common\Exception\UnsupportedTypeException;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class QuickAddRowCollectionBuilderTest extends WebTestCase
{
    private QuickAddRowCollectionBuilder $quickAddRowCollectionBuilder;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadProductData::class]);

        $this->quickAddRowCollectionBuilder = new QuickAddRowCollectionBuilder(
            self::getContainer()->get('doctrine')->getRepository(Product::class),
            self::getContainer()->get('oro_product.product.manager'),
            self::getContainer()->get('event_dispatcher'),
            self::getContainer()->get('oro_product.model.builder.quick_add_row_input_parser'),
            self::getContainer()->get('oro_security.acl_helper')
        );
        $this->quickAddRowCollectionBuilder
            ->setProductsGrouperFactory(self::getContainer()->get('oro_product.helper.product_grouper.factory'));
    }

    public function testBuildFromRequest(): void
    {
        $request = new Request([], [
            'oro_product_quick_add' => [
                'products' => [
                    [
                        'productSku' => LoadProductData::PRODUCT_1,
                        'productQuantity' => '1',
                        'productUnit' => 'item',
                    ],
                    [
                        'productSku' => 'SKIP1',
                        'productUnit' => 'item',
                    ],
                    [
                        'productSku' => LoadProductData::PRODUCT_7,
                        'productQuantity' => '2',
                        'productUnit' => 'item',
                    ],
                    [
                        'productQuantity' => '1',
                        'productUnit' => 'item',
                    ],
                    [
                        'productSku' => LoadProductData::PRODUCT_9,
                        'productQuantity' => '3',
                        'productUnit' => 'item',
                    ],
                    [
                        'productSku' => LoadProductData::PRODUCT_9,
                        'productQuantity' => '1',
                        'productUnit' => 'item',
                    ],
                ],
            ],
        ]);

        $quickAddRowCollection = $this->quickAddRowCollectionBuilder->buildFromRequest($request);

        self::assertEquals(3, $quickAddRowCollection->count());

        self::assertEquals(LoadProductData::PRODUCT_1, $quickAddRowCollection->get(0)->getSku());
        self::assertEquals('1', $quickAddRowCollection->get(0)->getQuantity());
        self::assertEquals('item', $quickAddRowCollection->get(0)->getUnit());
        self::assertInstanceOf(Product::class, $quickAddRowCollection->get(0)->getProduct());

        self::assertEquals(LoadProductData::PRODUCT_7, $quickAddRowCollection->get(1)->getSku());
        self::assertEquals('2', $quickAddRowCollection->get(1)->getQuantity());
        self::assertEquals('item', $quickAddRowCollection->get(1)->getUnit());
        self::assertInstanceOf(Product::class, $quickAddRowCollection->get(1)->getProduct());

        self::assertEquals(LoadProductData::PRODUCT_9, $quickAddRowCollection->get(2)->getSku());
        self::assertEquals('4', $quickAddRowCollection->get(2)->getQuantity());
        self::assertEquals('item', $quickAddRowCollection->get(2)->getUnit());
        self::assertNull($quickAddRowCollection->get(2)->getProduct());
    }

    public function testBuildFromCopyPasteText(): void
    {
        $text = <<<TEXT
1ABSC 1 item
2ABSC 2 item
2ABSC 1 item
TEXT;

        $quickAddRowCollection = $this->quickAddRowCollectionBuilder->buildFromCopyPasteText($text);

        self::assertEquals('1ABSC', $quickAddRowCollection->get(0)->getSku());
        self::assertEquals('1', $quickAddRowCollection->get(0)->getQuantity());
        self::assertEquals('item', $quickAddRowCollection->get(0)->getUnit());

        self::assertEquals('2ABSC', $quickAddRowCollection->get(1)->getSku());
        self::assertEquals('3', $quickAddRowCollection->get(1)->getQuantity());
        self::assertEquals('item', $quickAddRowCollection->get(1)->getUnit());
    }

    /**
     * @dataProvider uploadedFileProvider
     *
     * @param string $fileName
     */
    public function testBuildFromFile(string $fileName): void
    {
        $file = new UploadedFile(__DIR__ . '/data/' . $fileName, $fileName);
        $quickAddRowCollection = $this->quickAddRowCollectionBuilder->buildFromFile($file);

        self::assertEquals('1ABSC', $quickAddRowCollection->get(0)->getSku());
        self::assertEquals('1.0', $quickAddRowCollection->get(0)->getQuantity());
        self::assertEquals('item', $quickAddRowCollection->get(0)->getUnit());

        self::assertEquals('2ABSC', $quickAddRowCollection->get(1)->getSku());
        self::assertEquals('2.0', $quickAddRowCollection->get(1)->getQuantity());
        self::assertEquals('item', $quickAddRowCollection->get(1)->getUnit());
    }

    public function testBuildFromFileWithGrouping(): void
    {
        $file = new UploadedFile(__DIR__ . '/data/quick-order-group.csv', 'quick-order-group.csv');
        $quickAddRowCollection = $this->quickAddRowCollectionBuilder->buildFromFile($file);

        self::assertEquals('1ABSC', $quickAddRowCollection->get(0)->getSku());
        self::assertEquals(5.0, $quickAddRowCollection->get(0)->getQuantity());
        self::assertEquals('item', $quickAddRowCollection->get(0)->getUnit());

        self::assertEquals('2ABSC', $quickAddRowCollection->get(1)->getSku());
        self::assertEquals(3.0, $quickAddRowCollection->get(1)->getQuantity());
        self::assertEquals('item', $quickAddRowCollection->get(1)->getUnit());
    }

    public function testBuildFromFileFailsBecauseOfWrongFormat(): void
    {
        $file = new UploadedFile(__DIR__ . '/data/quick-order.odt', 'quick-order.odt');

        $this->expectException(UnsupportedTypeException::class);

        $this->quickAddRowCollectionBuilder->buildFromFile($file);
    }

    public function uploadedFileProvider(): array
    {
        return [
            ['quick-order.csv'],
            ['quick-order.ods'],
            ['quick-order.xlsx'],
        ];
    }
}
