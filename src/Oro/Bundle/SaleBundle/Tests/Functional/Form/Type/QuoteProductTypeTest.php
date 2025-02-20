<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class QuoteProductTypeTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroSaleBundle/Tests/Functional/Form/Type/DataFixtures/QuoteProductType.yml',
        ]);

        $request = Request::createFromGlobals();
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        self::getClientInstance()->getContainer()->get('request_stack')->push($request);
    }

    public function testCreateWhenNoData(): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(QuoteProductType::class, null, ['csrf_protection' => false]);

        $this->assertSimpleProductFormFields($form);

        self::assertTrue($form->has('kitItemLineItems'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
            ],
            $form->get('kitItemLineItems')->getConfig()->getOptions()
        );

        $formView = $form->createView();
        self::assertEquals(
            [
                'units' => [],
                'compactUnits' => false,
                'allUnits' => $this->getAllUnits(false),
                'typeOffer' => QuoteProduct::TYPE_OFFER,
                'typeReplacement' => QuoteProduct::TYPE_NOT_AVAILABLE,
                'isFreeForm' => false,
                'allowEditFreeForm' => true,
                'fullName' => 'oro_sale_quote_product',
                'productType' => null,
                'currency' => null
            ],
            $formView->vars['componentOptions']
        );
    }

    public function testCreateWhenProductSimpleAndCompactUnits(): void
    {
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        $productUnitItem = $this->getReference('item');
        $quoteProductOffer = (new QuoteProductOffer())
            ->setQuantity(12.3456)
            ->setProductUnit($productUnitItem);
        $quoteProduct = (new QuoteProduct())
            ->setProduct($productSimple1)
            ->addQuoteProductOffer($quoteProductOffer)
            ->setComment('Sample comment');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductType::class,
            $quoteProduct,
            [
                'compact_units' => true,
                'csrf_protection' => false,
            ]
        );

        $this->assertSimpleProductFormFields($form, true);

        self::assertTrue($form->has('kitItemLineItems'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
            ],
            $form->get('kitItemLineItems')->getConfig()->getOptions()
        );

        $formView = $form->createView();
        self::assertEquals(
            [
                'units' => [$productSimple1->getId() => $productSimple1->getAvailableUnitsPrecision()],
                'compactUnits' => true,
                'allUnits' => $this->getAllUnits(true),
                'typeOffer' => QuoteProduct::TYPE_OFFER,
                'typeReplacement' => QuoteProduct::TYPE_NOT_AVAILABLE,
                'isFreeForm' => false,
                'allowEditFreeForm' => true,
                'fullName' => 'oro_sale_quote_product',
                'productType' => 'simple',
                'currency' => null
            ],
            $formView->vars['componentOptions']
        );
    }

    public function testCreateWhenProductKit(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        $productUnitItem = $this->getReference('each');
        $quoteProductOffer = (new QuoteProductOffer())
            ->setQuantity(12.3456)
            ->setProductUnit($productUnitItem)
            ->setPrice(Price::create(10, 'USD'));
        $quoteProduct = (new QuoteProduct())
            ->setProduct($productKit1)
            ->addQuoteProductOffer($quoteProductOffer)
            ->setComment('Sample comment');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(QuoteProductType::class, $quoteProduct, ['csrf_protection' => false]);

        $this->assertSimpleProductFormFields($form, false, true);

        self::assertTrue($form->has('kitItemLineItems'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
            ],
            $form->get('kitItemLineItems')->getConfig()->getOptions()
        );

        self::assertCount(1, $form->get('kitItemLineItems'));

        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        self::assertTrue($form->get('kitItemLineItems')->has((string)$productKit1Item1->getId()));

        $kitItemLineItem = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1);
        $actualKitItemLineItem = $form->get('kitItemLineItems')->get((string)$productKit1Item1->getId())->getData();
        self::assertEquals($kitItemLineItem, $actualKitItemLineItem);

        $formView = $form->createView();
        self::assertContains('oro_sale_quote_product', $formView->vars['block_prefixes']);

        self::assertEquals(
            [
                'units' => [$productKit1->getId() => $productKit1->getAvailableUnitsPrecision()],
                'compactUnits' => false,
                'allUnits' => $this->getAllUnits(false),
                'typeOffer' => QuoteProduct::TYPE_OFFER,
                'typeReplacement' => QuoteProduct::TYPE_NOT_AVAILABLE,
                'isFreeForm' => false,
                'allowEditFreeForm' => true,
                'fullName' => 'oro_sale_quote_product',
                'productType' => 'kit',
                'currency' => 'USD'
            ],
            $formView->vars['componentOptions']
        );
    }

    public function testSubmitNewProductSimple(): void
    {
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        $productUnitItem = $this->getReference('item');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            'product' => $productSimple1->getId(),
            'quoteProductOffers' => [
                [
                    'quantity' => 123.456,
                    'productUnit' => $productUnitItem->getCode(),
                    'price' => [
                        'value' => 42.5678,
                        'currency' => 'USD',
                    ],
                ],
            ],
            'comment' => 'Sample comment',
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $quoteProductOffer = (new QuoteProductOffer())
            ->setQuantity(123.456)
            ->setProductUnit($productUnitItem)
            ->setPrice(Price::create(42.5678, 'USD'));
        $quoteProduct = (new QuoteProduct())
            ->setProduct($productSimple1)
            ->addQuoteProductOffer($quoteProductOffer)
            ->setComment('Sample comment');

        self::assertInstanceOf(QuoteProduct::class, $form->getData());

        /** @var QuoteProduct $actualQuoteProduct */
        $actualQuoteProduct = $form->getData();
        self::assertEquals($quoteProduct->getProduct()->getId(), $actualQuoteProduct->getProduct()->getId());

        self::assertCount(1, $actualQuoteProduct->getQuoteProductOffers());

        /** @var QuoteProductOffer $actualQuoteProductOffer1 */
        $actualQuoteProductOffer1 = $actualQuoteProduct->getQuoteProductOffers()->first();
        self::assertEquals($quoteProductOffer->getQuantity(), $actualQuoteProductOffer1->getQuantity());
        self::assertEquals(
            $quoteProductOffer->getProductUnit()->getCode(),
            $actualQuoteProductOffer1->getProductUnit()?->getCode()
        );
        self::assertEquals($quoteProductOffer->getPrice(), $actualQuoteProductOffer1->getPrice());
        self::assertNotEmpty($actualQuoteProductOffer1->getChecksum());

        self::assertEquals('Sample comment', $actualQuoteProduct->getComment());
    }

    public function testSubmitExistingProductSimple(): void
    {
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        $productUnitItem = $this->getReference('item');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');

        $quoteProductOffer = (new QuoteProductOffer())
            ->setQuantity(123.456)
            ->setProductUnit($productUnitItem)
            ->setPrice(Price::create(42.5678, 'USD'));
        $quoteProduct = (new QuoteProduct())
            ->setProduct($productSimple1)
            ->addQuoteProductOffer($quoteProductOffer)
            ->setComment('Sample comment');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductType::class,
            $quoteProduct,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            'product' => $productSimple2->getId(),
            'quoteProductOffers' => [
                [
                    'quantity' => 12.34,
                    'productUnit' => $productUnitItem->getCode(),
                    'price' => [
                        'value' => 42.1234,
                        'currency' => 'USD',
                    ],
                ],
            ],
            'comment' => 'Updated comment',
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(QuoteProduct::class, $form->getData());

        /** @var QuoteProduct $actualQuoteProduct */
        $actualQuoteProduct = $form->getData();
        self::assertEquals($productSimple2->getId(), $actualQuoteProduct->getProduct()->getId());

        self::assertCount(1, $actualQuoteProduct->getQuoteProductOffers());

        /** @var QuoteProductOffer $actualQuoteProductOffer1 */
        $actualQuoteProductOffer1 = $actualQuoteProduct->getQuoteProductOffers()->first();
        self::assertEquals(12.34, $actualQuoteProductOffer1->getQuantity());
        self::assertEquals(
            $productSimple2->getPrimaryUnitPrecision()->getProductUnitCode(),
            $actualQuoteProductOffer1->getProductUnit()?->getCode()
        );
        self::assertEquals(Price::create(42.1234, 'USD'), $actualQuoteProductOffer1->getPrice());
        self::assertNotEmpty($actualQuoteProductOffer1->getChecksum());

        self::assertEquals('Updated comment', $actualQuoteProduct->getComment());
    }

    public function testSubmitNewProductKit(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        $productUnitEach = $this->getReference('each');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            'product' => $productKit1->getId(),
            'kitItemLineItems' => [
                $productKit1Item1->getId() => [
                    'product' => $productSimple1->getId(),
                    'quantity' => 45.6789,
                ],
            ],
            'quoteProductOffers' => [
                [
                    'quantity' => 123,
                    'productUnit' => $productUnitEach->getCode(),
                    'price' => [
                        'value' => 42.5678,
                        'currency' => 'USD',
                    ],
                ],
            ],
            'comment' => 'Sample comment',
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $quoteProductOffer = (new QuoteProductOffer())
            ->setQuantity(123)
            ->setProductUnit($productUnitEach)
            ->setPrice(Price::create(42.5678, 'USD'));
        $kitItemLineItem = (new QuoteProductKitItemLineItem())
            ->setKitItem($productKit1Item1)
            ->setProduct($productSimple1)
            ->setQuantity(45.6789);
        $quoteProduct = (new QuoteProduct())
            ->setProduct($productKit1)
            ->addQuoteProductOffer($quoteProductOffer)
            ->addKitItemLineItem($kitItemLineItem)
            ->setComment('Sample comment');

        self::assertInstanceOf(QuoteProduct::class, $form->getData());

        /** @var QuoteProduct $actualQuoteProduct */
        $actualQuoteProduct = $form->getData();
        self::assertEquals($quoteProduct->getProduct()->getId(), $actualQuoteProduct->getProduct()->getId());

        self::assertCount(1, $actualQuoteProduct->getKitItemLineItems());

        /** @var QuoteProductKitItemLineItem $actualKitItemLineItem1 */
        $actualKitItemLineItem1 = $actualQuoteProduct->getKitItemLineItems()->first();
        self::assertEquals($kitItemLineItem->getKitItem()->getId(), $actualKitItemLineItem1->getKitItem()->getId());
        self::assertEquals(
            $kitItemLineItem->getProduct()->getId(),
            $actualKitItemLineItem1->getProduct()->getId()
        );
        self::assertEquals($kitItemLineItem->getQuantity(), $actualKitItemLineItem1->getQuantity());

        self::assertCount(1, $actualQuoteProduct->getQuoteProductOffers());

        /** @var QuoteProductOffer $actualQuoteProductOffer1 */
        $actualQuoteProductOffer1 = $actualQuoteProduct->getQuoteProductOffers()->first();
        self::assertEquals($quoteProductOffer->getQuantity(), $actualQuoteProductOffer1->getQuantity());
        self::assertEquals(
            $quoteProductOffer->getProductUnit()->getCode(),
            $actualQuoteProductOffer1->getProductUnit()?->getCode()
        );
        self::assertEquals($quoteProductOffer->getPrice(), $actualQuoteProductOffer1->getPrice());
        self::assertNotEmpty($actualQuoteProductOffer1->getChecksum());

        self::assertEquals('Sample comment', $actualQuoteProduct->getComment());

        $view = $form->createView();
        self::assertTrue(
            $view->children['quoteProductOffers'][0]->children['price']->children['value']->vars['attr']['readonly']
        );
    }

    public function testSubmitExistingProductKit(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        $productUnitEach = $this->getReference('each');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');

        $quoteProductOffer = (new QuoteProductOffer())
            ->setQuantity(123)
            ->setProductUnit($productUnitEach)
            ->setPrice(Price::create(42.5678, 'USD'));
        $kitItemLineItem = (new QuoteProductKitItemLineItem())
            ->setKitItem($productKit1Item1)
            ->setProduct($productSimple1)
            ->setQuantity(45.6789);
        $quoteProduct = (new QuoteProduct())
            ->setProduct($productKit1)
            ->addQuoteProductOffer($quoteProductOffer)
            ->addKitItemLineItem($kitItemLineItem)
            ->setComment('Sample comment');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductType::class,
            $quoteProduct,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            'product' => $quoteProduct->getProduct()->getId(),
            'kitItemLineItems' => [
                $productKit1Item1->getId() => [
                    'product' => $productSimple2->getId(),
                    'quantity' => 56.78,
                ],
            ],
            'quoteProductOffers' => [
                [
                    'quantity' => 12.34,
                    'productUnit' => $quoteProductOffer->getProductUnit()->getCode(),
                    'price' => [
                        'value' => 42.1234,
                        'currency' => 'USD',
                    ],
                ],
            ],
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(QuoteProduct::class, $form->getData());

        /** @var QuoteProduct $actualQuoteProduct */
        $actualQuoteProduct = $form->getData();
        self::assertEquals($quoteProduct->getProduct()->getId(), $actualQuoteProduct->getProduct()->getId());

        self::assertCount(1, $actualQuoteProduct->getKitItemLineItems());

        /** @var QuoteProductKitItemLineItem $actualKitItemLineItem1 */
        $actualKitItemLineItem1 = $actualQuoteProduct->getKitItemLineItems()->first();
        self::assertEquals(56.78, $actualKitItemLineItem1->getQuantity());
        self::assertEquals($productSimple2->getId(), $actualKitItemLineItem1->getProduct()->getId());

        self::assertCount(1, $actualQuoteProduct->getQuoteProductOffers());

        /** @var QuoteProductOffer $actualQuoteProductOffer1 */
        $actualQuoteProductOffer1 = $actualQuoteProduct->getQuoteProductOffers()->first();
        self::assertEquals(12.34, $actualQuoteProductOffer1->getQuantity());
        self::assertEquals(
            $quoteProductOffer->getProductUnit()->getCode(),
            $actualQuoteProductOffer1->getProductUnit()?->getCode()
        );
        self::assertEquals(Price::create(42.1234, 'USD'), $actualQuoteProductOffer1->getPrice());
        self::assertNotEmpty($actualQuoteProductOffer1->getChecksum());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function assertSimpleProductFormFields(
        FormInterface $form,
        bool $isCompactUnits = false,
        bool $allowPricesOverride = true,
    ): void {
        self::assertArrayIntersectEquals(
            [
                'data_class' => QuoteProduct::class,
                'compact_units' => $isCompactUnits,
                'csrf_token_id' => 'sale_quote_product',
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => ['view' => 'orosale/js/app/views/line-item-view'],
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('product'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'autocomplete_alias' => 'oro_sale_product_visibility_limited',
                'grid_name' => 'products-select-grid',
                'grid_parameters' => [
                    'types' => [Product::TYPE_SIMPLE, Product::TYPE_KIT]
                ],
                'create_enabled' => false,
                'data_parameters' => [
                    'scope' => 'quote',
                ],
            ],
            $form->get('product')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('productSku'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
            ],
            $form->get('productSku')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('productReplacement'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'create_enabled' => false,
                'data_parameters' => [
                    'scope' => 'quote'
                ]
            ],
            $form->get('productReplacement')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('productReplacementSku'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
            ],
            $form->get('productReplacementSku')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('freeFormProduct'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
            ],
            $form->get('freeFormProduct')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('freeFormProductReplacement'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
            ],
            $form->get('freeFormProductReplacement')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('quoteProductOffers'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'entry_options' => [
                    'compact_units' => $isCompactUnits,
                    'allow_prices_override' => $allowPricesOverride,
                ],
            ],
            $form->get('quoteProductOffers')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('type'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'data' => QuoteProduct::TYPE_REQUESTED,
            ],
            $form->get('type')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('commentCustomer'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'attr' => [
                    'readonly' => true
                ]
            ],
            $form->get('commentCustomer')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('comment'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
            ],
            $form->get('comment')->getConfig()->getOptions()
        );
    }

    private function getAllUnits(bool $isCompactUnits): array
    {
        $container = self::getContainer();
        $units = $container->get('doctrine')->getRepository(ProductUnit::class)->getAllUnits();

        return $container->get('oro_product.formatter.product_unit_label')->formatChoices($units, $isCompactUnits);
    }
}
