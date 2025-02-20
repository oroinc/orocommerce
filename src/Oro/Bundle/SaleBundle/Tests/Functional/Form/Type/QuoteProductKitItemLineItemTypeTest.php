<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Functional\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductKitItemLineItemType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\HttpFoundation\Request;

class QuoteProductKitItemLineItemTypeTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroSaleBundle/Tests/Functional/Form/Type/DataFixtures/QuoteProductKitItemLineItemType.yml',
            '@OroSaleBundle/Tests/Functional/Form/Type/DataFixtures/QuoteProductKitItemLineItemType.quote.yml',
        ]);

        $request = Request::createFromGlobals();
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        self::getClientInstance()->getContainer()->get('request_stack')->push($request);
    }

    public function testCreateWhenNoDataAndRequired(): void
    {
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductKitItemLineItemType::class,
            null,
            ['csrf_protection' => false, 'product_kit_item' => $productKit1Item1, 'required' => true]
        );

        self::assertArrayIntersectEquals(
            [
                'error_mapping' => ['.' => 'product'],
                'error_bubbling' => false,
                'data_class' => QuoteProductKitItemLineItem::class,
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('product'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'choices' => [$productSimple1, $productSimple2],
                'choice_value' => 'id',
                'choice_translation_domain' => false,
                'empty_data' => (string)$productSimple1->getId(),
            ],
            $form->get('product')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('quantity'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'useInputTypeNumberValueFormat' => true,
                'empty_data' => 1.0,
            ],
            $form->get('quantity')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('price'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'hide_currency' => true,
                'default_currency' => null,
            ],
            $form->get('price')->getConfig()->getOptions()
        );

        $kitItemLineItem = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1);
        $actualKitItemLineItem = $form->getData();
        self::assertEquals($kitItemLineItem, $actualKitItemLineItem);

        $formView = $form->createView();
        self::assertSame($productKit1Item1, $formView->vars['product_kit_item']);
        self::assertNull($formView->vars['currency']);
        self::assertContains('oro_sale_quote_product_kit_item_line_item', $formView->vars['block_prefixes']);
    }

    public function testCreateWhenNoDataAndNotRequired(): void
    {
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductKitItemLineItemType::class,
            null,
            ['csrf_protection' => false, 'product_kit_item' => $productKit1Item1, 'required' => false]
        );

        self::assertArrayIntersectEquals(
            [
                'error_mapping' => ['.' => 'product'],
                'error_bubbling' => false,
                'data_class' => QuoteProductKitItemLineItem::class,
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('product'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'expanded' => false,
                'multiple' => false,
                'choices' => [$productSimple1, $productSimple2],
                'choice_value' => 'id',
                'choice_translation_domain' => false,
                'empty_data' => null,
            ],
            $form->get('product')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('quantity'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'useInputTypeNumberValueFormat' => true,
                'empty_data' => 1.0,
            ],
            $form->get('quantity')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('price'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'hide_currency' => true,
                'default_currency' => null,
            ],
            $form->get('price')->getConfig()->getOptions()
        );

        $kitItemLineItem = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1);
        $actualKitItemLineItem = $form->getData();
        self::assertEquals($kitItemLineItem, $actualKitItemLineItem);

        $formView = $form->createView();
        self::assertSame($productKit1Item1, $formView->vars['product_kit_item']);
        self::assertNull($formView->vars['currency']);
    }

    public function testCreateShouldHaveDisabledQuantityAndPriceWhenHasDataAndNoProduct(): void
    {
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');

        $kitItemLineItem = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct(null);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductKitItemLineItemType::class,
            $kitItemLineItem,
            [
                'csrf_protection' => false,
                'product_kit_item' => $productKit1Item1,
                'required' => false,
                'currency' => 'USD'
            ]
        );

        self::assertArrayIntersectEquals(
            [
                'error_mapping' => ['.' => 'product'],
                'error_bubbling' => false,
                'data_class' => QuoteProductKitItemLineItem::class,
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('product'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'expanded' => false,
                'multiple' => false,
                'choices' => [$productSimple1, $productSimple2],
                'choice_value' => 'id',
                'choice_translation_domain' => false,
                'empty_data' => null,
            ],
            $form->get('product')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('quantity'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'useInputTypeNumberValueFormat' => true,
                'empty_data' => 1.0,
                'disabled' => true,
            ],
            $form->get('quantity')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('price'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'hide_currency' => true,
                'default_currency' => "USD",
                'disabled' => false,
            ],
            $form->get('price')->getConfig()->getOptions()
        );

        self::assertTrue($form->get('price')->get('value')->getConfig()->getOption('disabled'));
        self::assertTrue($form->get('price')->get('currency')->getConfig()->getOption('disabled'));

        $actualKitItemLineItem = $form->getData();
        self::assertEquals($kitItemLineItem, $actualKitItemLineItem);

        $formView = $form->createView();
        self::assertSame($productKit1Item1, $formView->vars['product_kit_item']);
        self::assertEquals('USD', $formView->vars['currency']);
    }

    public function testCreateShouldHaveGhostProduct(): void
    {
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');

        /** @var QuoteProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('quote_product_kit1_item1_line_item');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductKitItemLineItemType::class,
            $kitItemLineItem,
            ['csrf_protection' => false, 'product_kit_item' => $productKit1Item1, 'required' => false]
        );

        self::assertArrayIntersectEquals(
            [
                'error_mapping' => ['.' => 'product'],
                'error_bubbling' => false,
                'data_class' => QuoteProductKitItemLineItem::class,
            ],
            $form->getConfig()->getOptions()
        );

        $ghostProduct = (new Product())
            ->setSku('product_simple1')
            ->setDefaultName('ProductSimple1');

        ReflectionUtil::getProperty(new \ReflectionClass(Product::class), 'id')
            ?->setValue($ghostProduct, \PHP_INT_MIN);

        self::assertTrue($form->has('product'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'expanded' => false,
                'multiple' => false,
                'choices' => [$ghostProduct, $productSimple1, $productSimple2],
                'choice_value' => 'id',
                'choice_translation_domain' => false,
                'empty_data' => null,
            ],
            $form->get('product')->getConfig()->getOptions()
        );
    }

    public function testSubmitNew(): void
    {
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductKitItemLineItemType::class,
            null,
            [
                'csrf_protection' => false,
                'validation_groups' => false,
                'product_kit_item' => $productKit1Item1,
                'required' => false,
            ]
        );

        $form->submit([
            'product' => $productSimple1->getId(),
            'quantity' => 12.3456,
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(QuoteProductKitItemLineItem::class, $form->getData());

        $kitItemLineItem = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setQuantity(12.3456);

        self::assertEquals($kitItemLineItem, $form->getData());
    }

    public function testSubmitShouldHasDisabledQuantityWhenNoProduct(): void
    {
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductKitItemLineItemType::class,
            null,
            [
                'csrf_protection' => false,
                'validation_groups' => false,
                'product_kit_item' => $productKit1Item1,
                'required' => false,
            ]
        );

        $form->submit([
            'product' => '',
            'quantity' => 12.3456,
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertTrue($form->get('quantity')->getConfig()->getDisabled());

        self::assertInstanceOf(QuoteProductKitItemLineItem::class, $form->getData());

        $kitItemLineItem = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct(null);

        self::assertEquals($kitItemLineItem, $form->getData());
    }

    public function testSubmitExisting(): void
    {
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');

        $kitItemLineItem = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setQuantity(34.5678);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductKitItemLineItemType::class,
            $kitItemLineItem,
            [
                'csrf_protection' => false,
                'validation_groups' => false,
                'product_kit_item' => $productKit1Item1,
                'required' => false,
            ]
        );

        $form->submit([
            'product' => $productSimple2->getId(),
            'quantity' => 12.3456,
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(QuoteProductKitItemLineItem::class, $form->getData());

        $expectedKitItemLineItem = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple2)
            ->setQuantity(12.3456);

        self::assertEquals($expectedKitItemLineItem, $form->getData());
    }
}
