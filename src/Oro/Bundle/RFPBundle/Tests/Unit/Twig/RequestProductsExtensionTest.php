<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Twig;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Twig\RequestProductsExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestProductsExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private LocalizationHelper|MockObject $localizedHelper;

    private RequestProductsExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->localizedHelper = $this->createMock(LocalizationHelper::class);

        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $entityNameResolver->expects(self::any())
            ->method('getName')
            ->willReturnCallback(function (?ProductStub $entity) {
                if ($entity) {
                    return $entity->getDefaultName() ?? 'Item Sku';
                } else {
                    return 'Item Name';
                }
            });

        $container = self::getContainerBuilder()
            ->add(LocalizationHelper::class, $this->localizedHelper)
            ->add(EntityNameResolver::class, $entityNameResolver)
            ->getContainer($this);

        $this->extension = new RequestProductsExtension($container);
    }

    /**
     * @dataProvider getRequestProductsDataProvider
     */
    public function testGetRequestProducts(Request $request, array $expectedResult): void
    {
        $this->localizedHelper->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn (iterable $values) => $values[0]->getString());

        self::assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'rfp_products', [$request])
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getRequestProductsDataProvider(): array
    {
        $organization = (new Organization())->setName('Oro');

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

        $productKit1Name = 'Product Kit 1 Name';
        $productKit1Sku = 'productKit1Sku';
        $productKit1Comment1 = 'Product Kit 1 comment 1';
        $productKit1Unit = (new ProductUnit())->setCode('item');

        $sample1Product = (new ProductStub())
            ->setNames([(new ProductName())->setString($sample1Name)])
            ->setSku($sample1Sku)
            ->setOrganization($organization);

        $productKit1Product = (new ProductStub())
            ->setNames([(new ProductName())->setString($productKit1Name)])
            ->setSku($productKit1Sku)
            ->setOrganization($organization);

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

        $kitItemLabel = 'Kit Item Label';
        $kitItemLabels = [(new ProductKitItemLabel())->setString($kitItemLabel)];
        $kitItem = (new ProductKitItemStub(1))->setLabels($kitItemLabels);

        $kitItemLineItem = $this->createKitItemLineItem(
            1,
            $productKit1Unit,
            $sample1Product,
            $kitItem
        );

        $productKit1RequestProduct = (new RequestProduct())
            ->setProduct($productKit1Product)
            ->setComment($productKit1Comment1)
            ->addRequestProductItem($sample1RequestProductItem1)
            ->addRequestProductItem($sample1RequestProductItem2)
            ->addKitItemLineItem($kitItemLineItem);

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
            ->setSku($sample3Sku)
            ->setOrganization($organization);

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

        $requestWithProductKit = (new Request())
            ->addRequestProduct($sample1RequestProduct)
            ->addRequestProduct($productKit1RequestProduct);

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
                        'kitItemLineItems' => [],
                        'sellerName' => 'Oro'
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
                        'kitItemLineItems' => [],
                        'sellerName' => 'Oro'
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
                        'kitItemLineItems' => [],
                        'sellerName' => 'Oro'
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
                        'kitItemLineItems' => [],
                        'sellerName' => 'Oro'
                    ],
                ],
            ],
            'request with product kit' => [
                'request' => $requestWithProductKit,
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
                        'kitItemLineItems' => [],
                        'sellerName' => 'Oro'
                    ],
                    [
                        'name' => $productKit1Name,
                        'sku' => $productKit1Sku,
                        'comment' => $productKit1Comment1,
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
                        'kitItemLineItems' => [
                            [
                                'kitItemLabel' => $kitItemLabel,
                                'unit' => $productKit1Unit,
                                'quantity' => 1.0,
                                'productName' => $sample1Name,
                                'productSku' => $sample1Sku
                            ],
                        ],
                        'sellerName' => 'Oro'
                    ],
                ],
            ],
        ];
    }

    private function createKitItemLineItem(
        float $quantity,
        ?ProductUnit $productUnit,
        ?ProductStub $product,
        ?ProductKitItem $kitItem
    ): RequestProductKitItemLineItem {
        return (new RequestProductKitItemLineItem())
            ->setProduct($product)
            ->setKitItem($kitItem)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setSortOrder(1);
    }
}
