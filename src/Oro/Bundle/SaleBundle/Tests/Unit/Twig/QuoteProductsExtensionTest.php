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
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Twig\QuoteProductsExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteProductsExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private LocalizationHelper|MockObject $localizedHelper;

    private QuoteProductsExtension $extension;

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
                    return null;
                }
            });

        $container = self::getContainerBuilder()
            ->add(LocalizationHelper::class, $this->localizedHelper)
            ->add(EntityNameResolver::class, $entityNameResolver)
            ->getContainer($this);

        $this->extension = new QuoteProductsExtension($container);
    }

    /**
     * @dataProvider getQuoteProductsDataProvider
     */
    public function testGetQuoteProducts(Quote $quote, array $expectedResult): void
    {
        $this->localizedHelper->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn (iterable $values) => $values[0]->getString());

        self::assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'quote_products', [$quote])
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getQuoteProductsDataProvider(): array
    {
        $organization = (new Organization())->setName('Oro');

        $sampleCurrency = 'sampleCurrency';
        $sample1Sku = 'sample1Sku';
        $sample1Name = 'Sample 1 Name 1';
        $sample1Comment1 = 'sample 1 comment 1';
        $sample1CustomerComment1 = 'sample 1 customer comment 1';
        $sample1Quantity1 = 1;
        $sample1Quantity2 = 5;
        $sample1Price1 = Price::create(10, $sampleCurrency);
        $sample1Price2 = Price::create(7, $sampleCurrency);
        $sample1Unit1 = 'sample1Unit1';
        $sample1Unit2 = 'sample1Unit2';

        $sample2Comment1 = 'sample 2 comment 1';
        $sample2CustomerComment1 = 'sample 2 customer comment 1';
        $sample2Quantity1 = 1;
        $sample2Price1 = Price::create(12, $sampleCurrency);
        $sample2Unit1 = 'sample2Unit1';

        $sample3Sku = 'sample3Sku';
        $sample3Name = 'Sample 3 Name';
        $sample3Comment1 = 'sample 3 comment 1';
        $sample3CustomerComment1 = 'sample 3 Customer Comment 1';
        $sample3Quantity1 = 20;
        $sample3Price1 = Price::create(15, $sampleCurrency);
        $sample3Unit1 = 'sample3Unit1';

        $productKit1Name = 'Product Kit 1 Name';
        $productKit1Sku = 'productKit1Sku';
        $productKit1Comment1 = 'Product Kit 1 comment 1';
        $productKit1CustomerComment1 = 'Product Kit 1 customer comment 1';
        $productKit1Unit = (new ProductUnit())->setCode('item');

        $sample1Product = (new ProductStub())
            ->setNames([(new ProductName())->setString($sample1Name)])
            ->setSku($sample1Sku)
            ->setOrganization($organization);

        $productKit1Product = (new ProductStub())
            ->setNames([(new ProductName())->setString($productKit1Name)])
            ->setSku($productKit1Sku)
            ->setOrganization($organization);

        $sample1quoteProductOffer1 = (new QuoteProductOffer())
            ->setQuantity($sample1Quantity1)
            ->setPrice($sample1Price1)
            ->setProductUnitCode($sample1Unit1);

        $sample1quoteProductOffer2 = (new QuoteProductOffer())
            ->setQuantity($sample1Quantity2)
            ->setPrice($sample1Price2)
            ->setProductUnitCode($sample1Unit2);

        $sample1QuoteProduct = (new QuoteProduct())
            ->setProduct($sample1Product)
            ->setProductSku($sample1Product->getSku())
            ->setComment($sample1Comment1)
            ->setCommentCustomer($sample1CustomerComment1)
            ->addQuoteProductOffer($sample1quoteProductOffer1)
            ->addQuoteProductOffer($sample1quoteProductOffer2);

        $kitItemLabel = 'Kit Item Label';
        $kitItemLabels = [(new ProductKitItemLabel())->setString($kitItemLabel)];
        $kitItem = (new ProductKitItemStub(1))->setLabels($kitItemLabels);

        $kitItemLineItem = $this->createKitItemLineItem(
            1,
            $productKit1Unit,
            $sample1Product,
            $kitItem
        );

        $productKit1QuoteProduct = (new QuoteProduct())
            ->setProduct($productKit1Product)
            ->setProductSku($productKit1Product->getSku())
            ->setComment($productKit1Comment1)
            ->setCommentCustomer($productKit1CustomerComment1)
            ->addQuoteProductOffer($sample1quoteProductOffer1)
            ->addQuoteProductOffer($sample1quoteProductOffer2)
            ->addKitItemLineItem($kitItemLineItem);

        $sample2Product = clone $sample1Product;

        $sample2QuoteProductOffer1 =  (new QuoteProductOffer())
            ->setQuantity($sample2Quantity1)
            ->setPrice($sample2Price1)
            ->setProductUnitCode($sample2Unit1);

        $sample2QuoteProduct = (new QuoteProduct())
            ->setProduct($sample2Product)
            ->setProductSku($sample2Product->getSku())
            ->setComment($sample2Comment1)
            ->setCommentCustomer($sample2CustomerComment1)
            ->addQuoteProductOffer($sample2QuoteProductOffer1);

        $sample3Product = (new ProductStub())
            ->setNames([(new ProductName())->setString($sample3Name)])
            ->setSku($sample3Sku)
            ->setOrganization($organization);

        $sample3QuoteProductOffer1 = (new QuoteProductOffer())
            ->setQuantity($sample3Quantity1)
            ->setPrice($sample3Price1)
            ->setProductUnitCode($sample3Unit1);

        $sample3QuoteProduct = (new QuoteProduct())
            ->setProduct($sample3Product)
            ->setProductSku($sample3Product->getSku())
            ->setComment($sample3Comment1)
            ->setCommentCustomer($sample3CustomerComment1)
            ->addQuoteProductOffer($sample3QuoteProductOffer1);

        $sample3NameFreeForm = 'Sample 3 Name FreeForm';
        $sample5QuoteProduct = (new QuoteProduct())
            ->setProductSku($sample3Product->getSku())
            ->setFreeFormProduct($sample3NameFreeForm)
            ->setComment($sample3Comment1)
            ->setCommentCustomer($sample3CustomerComment1);

        $quote1 = (new Quote())
            ->addQuoteProduct($sample1QuoteProduct)
            ->addQuoteProduct($sample2QuoteProduct)
            ->addQuoteProduct($sample3QuoteProduct)
            ->addQuoteProduct($sample5QuoteProduct);

        $sample4QuoteProduct = (new QuoteProduct())
            ->setProduct($sample3Product)
            ->setProductSku($sample3Product->getSku())
            ->setComment($sample3Comment1)
            ->setCommentCustomer($sample3CustomerComment1);

        $quote2 = (new Quote())
            ->addQuoteProduct($sample4QuoteProduct);

        $quote3 = new Quote();

        $quoteWithProductKit = (new Quote())
            ->addQuoteProduct($sample1QuoteProduct)
            ->addQuoteProduct($productKit1QuoteProduct);

        return [
            'when no quote products' => [
                'quote' => $quote3,
                'expectedResult' => [],
            ],
            'when no quote product offers' => [
                'quote' => $quote2,
                'expectedResult' => [
                    [
                        'name' => $sample3Name,
                        'sku' => $sample3Sku,
                        'comment' => $sample3Comment1,
                        'commentCustomer' => $sample3CustomerComment1,
                        'items' => [],
                        'kitItemLineItems' => [],
                        'sellerName' => 'Oro'
                    ],
                ],
            ],
            'when quote with different quote offers' => [
                'quote' => $quote1,
                'expectedResult' => [
                    [
                        'name' => $sample1Name,
                        'sku' => $sample1Sku,
                        'comment' => $sample1Comment1,
                        'commentCustomer' => $sample1CustomerComment1,
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
                        'commentCustomer' => $sample2CustomerComment1,
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
                        'commentCustomer' => $sample3CustomerComment1,
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
                    [
                        'name' => $sample3NameFreeForm,
                        'sku' => $sample3Sku,
                        'comment' => $sample3Comment1,
                        'commentCustomer' => $sample3CustomerComment1,
                        'items' => [],
                        'kitItemLineItems' => [],
                        'sellerName' => null,
                    ],
                ],
            ],
            'quote with product kit' => [
                'quote' => $quoteWithProductKit,
                'expectedResult' => [
                    [
                        'name' => $sample1Name,
                        'sku' => $sample1Sku,
                        'comment' => $sample1Comment1,
                        'commentCustomer' => $sample1CustomerComment1,
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
                        'commentCustomer' => $productKit1CustomerComment1,
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
    ): QuoteProductKitItemLineItem {
        return (new QuoteProductKitItemLineItem())
            ->setProduct($product)
            ->setKitItem($kitItem)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setSortOrder(1);
    }
}
