<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class RequestProductTypeTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroRFPBundle/Tests/Functional/Form/Type/DataFixtures/RequestProductType.yml',
        ]);

        $request = Request::createFromGlobals();
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        self::getClientInstance()->getContainer()->get('request_stack')->push($request);
    }

    public function testCreateWhenNoData(): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(RequestProductType::class, null, ['csrf_protection' => false]);

        self::assertArrayIntersectEquals(
            [
                'data_class' => RequestProduct::class,
                'compact_units' => false,
                'csrf_token_id' => 'rfp_request_product',
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => ['view' => 'ororfp/js/app/views/line-item-view'],
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('product'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'autocomplete_alias' => 'oro_rfp_product_visibility_limited',
                'grid_name' => 'products-select-grid',
                'grid_parameters' => [
                    'types' => [Product::TYPE_SIMPLE, Product::TYPE_KIT]
                ],
                'create_enabled' => false,
                'data_parameters' => [
                    'scope' => 'rfp',
                ],
            ],
            $form->get('product')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('requestProductItems'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'entry_options' => [
                    'compact_units' => false,
                ],
            ],
            $form->get('requestProductItems')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('comment'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                StripTagsExtension::OPTION_NAME => true,
            ],
            $form->get('comment')->getConfig()->getOptions()
        );

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
            ],
            $formView->vars['componentOptions']
        );
    }

    public function testCreateWhenProductSimple(): void
    {
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        $productUnitItem = $this->getReference('item');
        $requestProductItem = (new RequestProductItem())
            ->setQuantity(12.3456)
            ->setProductUnit($productUnitItem);
        $requestProduct = (new RequestProduct())
            ->setProduct($productSimple1)
            ->addRequestProductItem($requestProductItem)
            ->setComment('Sample comment');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(RequestProductType::class, $requestProduct, ['csrf_protection' => false]);

        self::assertArrayIntersectEquals(
            [
                'data_class' => RequestProduct::class,
                'compact_units' => false,
                'csrf_token_id' => 'rfp_request_product',
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => ['view' => 'ororfp/js/app/views/line-item-view'],
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('product'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'autocomplete_alias' => 'oro_rfp_product_visibility_limited',
                'grid_name' => 'products-select-grid',
                'grid_parameters' => [
                    'types' => [Product::TYPE_SIMPLE, Product::TYPE_KIT]
                ],
                'create_enabled' => false,
                'data_parameters' => [
                    'scope' => 'rfp',
                ],
            ],
            $form->get('product')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('requestProductItems'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'entry_options' => [
                    'compact_units' => false,
                ],
            ],
            $form->get('requestProductItems')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('comment'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                StripTagsExtension::OPTION_NAME => true,
            ],
            $form->get('comment')->getConfig()->getOptions()
        );

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
                'compactUnits' => false,
            ],
            $formView->vars['componentOptions']
        );
    }

    public function testCreateWhenProductKit(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        $productUnitItem = $this->getReference('each');
        $requestProductItem = (new RequestProductItem())
            ->setQuantity(12.3456)
            ->setProductUnit($productUnitItem);
        $requestProduct = (new RequestProduct())
            ->setProduct($productKit1)
            ->addRequestProductItem($requestProductItem)
            ->setComment('Sample comment');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(RequestProductType::class, $requestProduct, ['csrf_protection' => false]);

        self::assertArrayIntersectEquals(
            [
                'data_class' => RequestProduct::class,
                'compact_units' => false,
                'csrf_token_id' => 'rfp_request_product',
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => ['view' => 'ororfp/js/app/views/line-item-view'],
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('product'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'autocomplete_alias' => 'oro_rfp_product_visibility_limited',
                'grid_name' => 'products-select-grid',
                'grid_parameters' => [
                    'types' => [Product::TYPE_SIMPLE, Product::TYPE_KIT]
                ],
                'create_enabled' => false,
                'data_parameters' => [
                    'scope' => 'rfp',
                ],
            ],
            $form->get('product')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('requestProductItems'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'entry_options' => [
                    'compact_units' => false,
                ],
            ],
            $form->get('requestProductItems')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('comment'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                StripTagsExtension::OPTION_NAME => true,
            ],
            $form->get('comment')->getConfig()->getOptions()
        );

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
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1);
        $actualKitItemLineItem = $form->get('kitItemLineItems')->get((string)$productKit1Item1->getId())->getData();
        self::assertEquals($kitItemLineItem, $actualKitItemLineItem);

        $formView = $form->createView();
        self::assertContains('oro_rfp_request_product', $formView->vars['block_prefixes']);

        self::assertEquals(
            [
                'units' => [$productKit1->getId() => $productKit1->getAvailableUnitsPrecision()],
                'compactUnits' => false,
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
            RequestProductType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            'product' => $productSimple1->getId(),
            'requestProductItems' => [
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

        $requestProductItem = (new RequestProductItem())
            ->setQuantity(123.456)
            ->setProductUnit($productUnitItem)
            ->setPrice(Price::create(42.5678, 'USD'));
        $requestProduct = (new RequestProduct())
            ->setProduct($productSimple1)
            ->addRequestProductItem($requestProductItem)
            ->setComment('Sample comment');

        self::assertInstanceOf(RequestProduct::class, $form->getData());

        /** @var RequestProduct $actualRequestProduct */
        $actualRequestProduct = $form->getData();
        self::assertEquals($requestProduct->getProduct()->getId(), $actualRequestProduct->getProduct()->getId());

        self::assertCount(1, $actualRequestProduct->getRequestProductItems());

        /** @var RequestProductItem $actualRequestProductItem1 */
        $actualRequestProductItem1 = $actualRequestProduct->getRequestProductItems()->first();
        self::assertEquals($requestProductItem->getQuantity(), $actualRequestProductItem1->getQuantity());
        self::assertEquals(
            $requestProductItem->getProductUnit()->getCode(),
            $actualRequestProductItem1->getProductUnit()?->getCode()
        );
        self::assertEquals($requestProductItem->getPrice(), $actualRequestProductItem1->getPrice());

        $checksumGenerator = self::getContainer()->get('oro_product.line_item_checksum_generator');

        self::assertEquals(
            $checksumGenerator->getChecksum($requestProductItem),
            $actualRequestProductItem1->getChecksum()
        );

        self::assertEquals('Sample comment', $actualRequestProduct->getComment());
    }

    public function testSubmitExistingProductSimple(): void
    {
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        $productUnitItem = $this->getReference('item');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');

        $requestProductItem = (new RequestProductItem())
            ->setQuantity(123.456)
            ->setProductUnit($productUnitItem)
            ->setPrice(Price::create(42.5678, 'USD'));
        $requestProduct = (new RequestProduct())
            ->setProduct($productSimple1)
            ->addRequestProductItem($requestProductItem)
            ->setComment('Sample comment');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductType::class,
            $requestProduct,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            'product' => $productSimple2->getId(),
            'requestProductItems' => [
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

        self::assertInstanceOf(RequestProduct::class, $form->getData());

        /** @var RequestProduct $actualRequestProduct */
        $actualRequestProduct = $form->getData();
        self::assertEquals($productSimple2->getId(), $actualRequestProduct->getProduct()->getId());

        self::assertCount(1, $actualRequestProduct->getRequestProductItems());

        /** @var RequestProductItem $actualRequestProductItem1 */
        $actualRequestProductItem1 = $actualRequestProduct->getRequestProductItems()->first();
        self::assertEquals(12.34, $actualRequestProductItem1->getQuantity());
        self::assertEquals(
            $productSimple2->getPrimaryUnitPrecision()->getProductUnitCode(),
            $actualRequestProductItem1->getProductUnit()?->getCode()
        );
        self::assertEquals(Price::create(42.1234, 'USD'), $actualRequestProductItem1->getPrice());

        $checksumGenerator = self::getContainer()->get('oro_product.line_item_checksum_generator');

        self::assertEquals(
            $checksumGenerator->getChecksum($requestProductItem),
            $actualRequestProductItem1->getChecksum()
        );

        self::assertEquals('Updated comment', $actualRequestProduct->getComment());
    }

    public function testSubmitNewProductKit(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        $productUnitEach = $this->getReference('each');
        $productUnitItem = $this->getReference('item');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductType::class,
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
            'requestProductItems' => [
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

        $requestProductItem = (new RequestProductItem())
            ->setQuantity(123)
            ->setProductUnit($productUnitEach)
            ->setPrice(Price::create(42.5678, 'USD'));
        $kitItemLineItem = (new RequestProductKitItemLineItem())
            ->setKitItem($productKit1Item1)
            ->setProduct($productSimple1)
            ->setQuantity(45.6789)
            ->setProductUnit($productUnitItem);
        $requestProduct = (new RequestProduct())
            ->setProduct($productKit1)
            ->addRequestProductItem($requestProductItem)
            ->addKitItemLineItem($kitItemLineItem)
            ->setComment('Sample comment');

        self::assertInstanceOf(RequestProduct::class, $form->getData());

        /** @var RequestProduct $actualRequestProduct */
        $actualRequestProduct = $form->getData();
        self::assertEquals($requestProduct->getProduct()->getId(), $actualRequestProduct->getProduct()->getId());

        self::assertCount(1, $actualRequestProduct->getKitItemLineItems());

        /** @var RequestProductKitItemLineItem $actualKitItemLineItem1 */
        $actualKitItemLineItem1 = $actualRequestProduct->getKitItemLineItems()->first();
        self::assertEquals($kitItemLineItem->getKitItem()->getId(), $actualKitItemLineItem1->getKitItem()->getId());
        self::assertEquals(
            $kitItemLineItem->getProduct()->getId(),
            $actualKitItemLineItem1->getProduct()->getId()
        );
        self::assertEquals($kitItemLineItem->getQuantity(), $actualKitItemLineItem1->getQuantity());

        self::assertCount(1, $actualRequestProduct->getRequestProductItems());

        /** @var RequestProductItem $actualRequestProductItem1 */
        $actualRequestProductItem1 = $actualRequestProduct->getRequestProductItems()->first();
        self::assertEquals($requestProductItem->getQuantity(), $actualRequestProductItem1->getQuantity());
        self::assertEquals(
            $requestProductItem->getProductUnit()->getCode(),
            $actualRequestProductItem1->getProductUnit()?->getCode()
        );
        self::assertEquals($requestProductItem->getPrice(), $actualRequestProductItem1->getPrice());

        $checksumGenerator = self::getContainer()->get('oro_product.line_item_checksum_generator');

        self::assertEquals(
            $checksumGenerator->getChecksum($requestProductItem),
            $actualRequestProductItem1->getChecksum()
        );

        self::assertEquals('Sample comment', $actualRequestProduct->getComment());
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

        $requestProductItem = (new RequestProductItem())
            ->setQuantity(123)
            ->setProductUnit($productUnitEach)
            ->setPrice(Price::create(42.5678, 'USD'));
        $kitItemLineItem = (new RequestProductKitItemLineItem())
            ->setKitItem($productKit1Item1)
            ->setProduct($productSimple1)
            ->setQuantity(45.6789);
        $requestProduct = (new RequestProduct())
            ->setProduct($productKit1)
            ->addRequestProductItem($requestProductItem)
            ->addKitItemLineItem($kitItemLineItem)
            ->setComment('Sample comment');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductType::class,
            $requestProduct,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            'product' => $requestProduct->getProduct()->getId(),
            'kitItemLineItems' => [
                $productKit1Item1->getId() => [
                    'product' => $productSimple2->getId(),
                    'quantity' => 56.78,
                ],
            ],
            'requestProductItems' => [
                [
                    'quantity' => 12.34,
                    'productUnit' => $requestProductItem->getProductUnit()->getCode(),
                    'price' => [
                        'value' => 42.1234,
                        'currency' => 'USD',
                    ],
                ],
            ],
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(RequestProduct::class, $form->getData());

        /** @var RequestProduct $actualRequestProduct */
        $actualRequestProduct = $form->getData();
        self::assertEquals($requestProduct->getProduct()->getId(), $actualRequestProduct->getProduct()->getId());

        self::assertCount(1, $actualRequestProduct->getKitItemLineItems());

        /** @var RequestProductKitItemLineItem $actualKitItemLineItem1 */
        $actualKitItemLineItem1 = $actualRequestProduct->getKitItemLineItems()->first();
        self::assertEquals(56.78, $actualKitItemLineItem1->getQuantity());
        self::assertEquals($productSimple2->getId(), $actualKitItemLineItem1->getProduct()->getId());

        self::assertCount(1, $actualRequestProduct->getRequestProductItems());

        /** @var RequestProductItem $actualRequestProductItem1 */
        $actualRequestProductItem1 = $actualRequestProduct->getRequestProductItems()->first();
        self::assertEquals(12.34, $actualRequestProductItem1->getQuantity());
        self::assertEquals(
            $requestProductItem->getProductUnit()->getCode(),
            $actualRequestProductItem1->getProductUnit()?->getCode()
        );
        self::assertEquals(Price::create(42.1234, 'USD'), $actualRequestProductItem1->getPrice());

        $checksumGenerator = self::getContainer()->get('oro_product.line_item_checksum_generator');

        self::assertEquals(
            $checksumGenerator->getChecksum($requestProductItem),
            $actualRequestProductItem1->getChecksum()
        );
    }
}
