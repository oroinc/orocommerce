<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Model\Builder;

use Box\Spout\Common\Exception\UnsupportedTypeException;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class QuickAddRowCollectionBuilderTest extends WebTestCase
{
    /** @var QuickAddRowCollectionBuilder */
    private $quickAddRowCollectionBuilder;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->quickAddRowCollectionBuilder = new QuickAddRowCollectionBuilder(
            $this->getContainer()->get('doctrine')->getRepository(Product::class),
            $this->getContainer()->get('oro_product.product.manager'),
            $this->getContainer()->get('event_dispatcher'),
            $this->getContainer()->get('oro_product.model.builder.quick_add_row_input_parser')
        );
    }

    public function testBuildFromRequest()
    {
        $request = new Request([], [
            'oro_product_quick_add' => [
                'products' => [
                    [
                        'productSku' => '1ABSC',
                        'productQuantity' => '1',
                        'productUnit' => 'item'
                    ],
                    [
                        'productSku' => 'SKIP1',
                        'productUnit' => 'item',
                    ],
                    [
                        'productSku' => '2ABSC',
                        'productQuantity' => '2',
                        'productUnit' => 'item'
                    ],
                    [
                        'productQuantity' => '1',
                        'productUnit' => 'item',
                    ],
                ]
            ]
        ]);

        $quickAddRowCollection = $this->quickAddRowCollectionBuilder->buildFromRequest($request);

        $this->assertEquals('1ABSC', $quickAddRowCollection->get(0)->getSku());
        $this->assertEquals('1', $quickAddRowCollection->get(0)->getQuantity());
        $this->assertEquals('item', $quickAddRowCollection->get(0)->getUnit());

        $this->assertEquals('2ABSC', $quickAddRowCollection->get(1)->getSku());
        $this->assertEquals('2', $quickAddRowCollection->get(1)->getQuantity());
        $this->assertEquals('item', $quickAddRowCollection->get(1)->getUnit());
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

    public function uploadedFileProvider()
    {
        return [
                ['quick-order.csv'], ['quick-order.ods'], ['quick-order.xlsx']
        ];
    }
}
