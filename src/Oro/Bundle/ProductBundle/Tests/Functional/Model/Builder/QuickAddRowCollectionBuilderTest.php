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
    /** @var QuickAddRowCollectionBuilder */
    private $quickAddRowCollectionBuilder;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadProductData::class]);

        $this->quickAddRowCollectionBuilder = new QuickAddRowCollectionBuilder(
            $this->getContainer()->get('doctrine')->getRepository(Product::class),
            $this->getContainer()->get('oro_product.product.manager'),
            $this->getContainer()->get('event_dispatcher'),
            $this->getContainer()->get('oro_product.model.builder.quick_add_row_input_parser'),
            $this->getContainer()->get('oro_security.acl_helper')
        );
    }

    public function testBuildFromRequest()
    {
        $request = new Request([], [
            'oro_product_quick_add' => [
                'products' => [
                    [
                        'productSku' => LoadProductData::PRODUCT_1,
                        'productQuantity' => '1',
                        'productUnit' => 'item'
                    ],
                    [
                        'productSku' => 'SKIP1',
                        'productUnit' => 'item',
                    ],
                    [
                        'productSku' => LoadProductData::PRODUCT_7,
                        'productQuantity' => '2',
                        'productUnit' => 'item'
                    ],
                    [
                        'productQuantity' => '1',
                        'productUnit' => 'item',
                    ],
                    [
                        'productSku' => LoadProductData::PRODUCT_9,
                        'productQuantity' => '3',
                        'productUnit' => 'item'
                    ],
                ]
            ]
        ]);

        $quickAddRowCollection = $this->quickAddRowCollectionBuilder->buildFromRequest($request);

        $this->assertEquals(3, $quickAddRowCollection->count());

        $this->assertEquals(LoadProductData::PRODUCT_1, $quickAddRowCollection->get(0)->getSku());
        $this->assertEquals('1', $quickAddRowCollection->get(0)->getQuantity());
        $this->assertEquals('item', $quickAddRowCollection->get(0)->getUnit());
        $this->assertInstanceOf(Product::class, $quickAddRowCollection->get(0)->getProduct());

        $this->assertEquals(LoadProductData::PRODUCT_7, $quickAddRowCollection->get(1)->getSku());
        $this->assertEquals('2', $quickAddRowCollection->get(1)->getQuantity());
        $this->assertEquals('item', $quickAddRowCollection->get(1)->getUnit());
        $this->assertInstanceOf(Product::class, $quickAddRowCollection->get(1)->getProduct());

        $this->assertEquals(LoadProductData::PRODUCT_9, $quickAddRowCollection->get(2)->getSku());
        $this->assertEquals('3', $quickAddRowCollection->get(2)->getQuantity());
        $this->assertEquals('item', $quickAddRowCollection->get(2)->getUnit());
        $this->assertNull($quickAddRowCollection->get(2)->getProduct());
    }

    public function testBuildFromCopyPasteText()
    {
        $text = <<<TEXT
1ABSC 1 item
2ABSC 2 item
TEXT;

        $quickAddRowCollection = $this->quickAddRowCollectionBuilder->buildFromCopyPasteText($text);

        $this->assertEquals('1ABSC', $quickAddRowCollection->get(0)->getSku());
        $this->assertEquals('1', $quickAddRowCollection->get(0)->getQuantity());
        $this->assertEquals('item', $quickAddRowCollection->get(0)->getUnit());

        $this->assertEquals('2ABSC', $quickAddRowCollection->get(1)->getSku());
        $this->assertEquals('2', $quickAddRowCollection->get(1)->getQuantity());
        $this->assertEquals('item', $quickAddRowCollection->get(1)->getUnit());
    }

    /**
     * @dataProvider uploadedFileProvider
     *
     * @param string $fileName
     */
    public function testBuildFromFile($fileName)
    {
        $file = new UploadedFile(__DIR__ . '/data/'.$fileName, $fileName);
        $quickAddRowCollection = $this->quickAddRowCollectionBuilder->buildFromFile($file);

        $this->assertEquals('1ABSC', $quickAddRowCollection->get(0)->getSku());
        $this->assertEquals('1.0', $quickAddRowCollection->get(0)->getQuantity());
        $this->assertEquals('item', $quickAddRowCollection->get(0)->getUnit());

        $this->assertEquals('2ABSC', $quickAddRowCollection->get(1)->getSku());
        $this->assertEquals('2.0', $quickAddRowCollection->get(1)->getQuantity());
        $this->assertEquals('item', $quickAddRowCollection->get(1)->getUnit());
    }

    public function testBuildFromFileFailsBecauseOfWrongFormat()
    {
        $file = new UploadedFile(__DIR__ . '/data/quick-order.odt', 'quick-order.odt');

        $this->expectException(UnsupportedTypeException::class);

        $this->quickAddRowCollectionBuilder->buildFromFile($file);
    }

    /**
     * @return array
     */
    public function uploadedFileProvider()
    {
        return [
                ['quick-order.csv'], ['quick-order.ods'], ['quick-order.xlsx']
        ];
    }
}
