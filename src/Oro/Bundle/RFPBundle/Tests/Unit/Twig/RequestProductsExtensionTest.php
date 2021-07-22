<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Twig;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Twig\RequestProductsExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class RequestProductsExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var RequestProductsExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new RequestProductsExtension();
    }

    /**
     * @dataProvider getRequestProductsDataProvider
     */
    public function testGetRequestProducts(Request $request, array $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'rfp_products', [$request])
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getRequestProductsDataProvider(): array
    {
        $sampleCurrency = 'sampleCurrency';
        $sample1Sku = 'sample1Sku';
        $sample1Name = 'Sample 1 Name';
        $sample1Comment1 = 'sample 1 comment 1';
        $sample1Quantity1 = 1;
        $sample1Quantity2 = 5;
        $sample1Price1 = Price::create(10, $sampleCurrency);
        $sample1Price2 = Price::create(7, $sampleCurrency);
        $sample1Unit1 = 'sample1Unit1';
        $sample1Unit2 = 'sample1Unit2';

        $sample2Comment1 = 'sample 2 comment 1';
        $sample2Quantity1 = 1;
        $sample2Price1 = Price::create(12, $sampleCurrency);
        $sample2Unit1 = 'sample2Unit1';

        $sample3Sku = 'sample3Sku';
        $sample3Name = 'Sample 3 Name';
        $sample3Comment1 = 'sample 3 comment 1';
        $sample3Quantity1 = 20;
        $sample3Price1 = Price::create(15, $sampleCurrency);
        $sample3Unit1 = 'sample3Unit1';

        $sample1Product = (new ProductStub())
            ->setNames([(new ProductName())->setString($sample1Name)])
            ->setSku($sample1Sku);

        $sample1RequestProductItem1 = (new RequestProductItem())
            ->setQuantity($sample1Quantity1)
            ->setPrice($sample1Price1)
            ->setProductUnitCode($sample1Unit1);

        $sample1RequestProductItem2 = (new RequestProductItem())
            ->setQuantity($sample1Quantity2)
            ->setPrice($sample1Price2)
            ->setProductUnitCode($sample1Unit2);

        $sample1RequestProduct = (new RequestProduct())
            ->setProduct($sample1Product)
            ->setComment($sample1Comment1)
            ->addRequestProductItem($sample1RequestProductItem1)
            ->addRequestProductItem($sample1RequestProductItem2);

        $sample2Product = clone $sample1Product;

        $sample2RequestProductItem1 = (new RequestProductItem())
            ->setQuantity($sample2Quantity1)
            ->setPrice($sample2Price1)
            ->setProductUnitCode($sample2Unit1);

        $sample2RequestProduct = (new RequestProduct())
            ->setProduct($sample2Product)
            ->setComment($sample2Comment1)
            ->addRequestProductItem($sample2RequestProductItem1);

        $sample3Product = (new ProductStub())
            ->setNames([(new ProductName())->setString($sample3Name)])
            ->setSku($sample3Sku);

        $sample3RequestProductItem1 = (new RequestProductItem())
            ->setQuantity($sample3Quantity1)
            ->setPrice($sample3Price1)
            ->setProductUnitCode($sample3Unit1);

        $sample3RequestProduct = (new RequestProduct())
            ->setProduct($sample3Product)
            ->setComment($sample3Comment1)
            ->addRequestProductItem($sample3RequestProductItem1);

        $request1 = (new Request())
            ->addRequestProduct($sample1RequestProduct)
            ->addRequestProduct($sample2RequestProduct)
            ->addRequestProduct($sample3RequestProduct);

        $sample4RequestProduct = (new RequestProduct())
            ->setComment($sample3Comment1)
            ->setProduct($sample3Product);

        $request2 = (new Request())
            ->addRequestProduct($sample4RequestProduct);

        $request3 = new Request();

        return [
            'when no request products' => [
                'request' => $request3,
                'expectedResult' => [],
            ],
            'when no request product items' => [
                'request' => $request2,
                'expectedResult' => [
                    [
                        'name' => $sample3Name,
                        'sku' => $sample3Sku,
                        'comment' => $sample3Comment1,
                        'items' => [],
                    ],
                ],
            ],
            'when requested same products with different quantities' => [
                'request' => $request1,
                'expectedResult' => [
                    [
                        'name' => $sample1Name,
                        'sku' => $sample1Sku,
                        'comment' => $sample1Comment1,
                        'items' => [
                            [
                                'quantity' => $sample1Quantity1,
                                'price' => $sample1Price1,
                                'unit' => $sample1Unit1,
                            ],
                            [
                                'quantity' => $sample1Quantity2,
                                'price' => $sample1Price2,
                                'unit' => $sample1Unit2,
                            ],
                        ],
                    ],
                    [
                        'name' => $sample1Name,
                        'sku' => $sample1Sku,
                        'comment' => $sample2Comment1,
                        'items' => [
                            [
                                'quantity' => $sample2Quantity1,
                                'price' => $sample2Price1,
                                'unit' => $sample2Unit1,
                            ],
                        ],
                    ],
                    [
                        'name' => $sample3Name,
                        'sku' => $sample3Sku,
                        'comment' => $sample3Comment1,
                        'items' => [
                            [
                                'quantity' => $sample3Quantity1,
                                'price' => $sample3Price1,
                                'unit' => $sample3Unit1,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
