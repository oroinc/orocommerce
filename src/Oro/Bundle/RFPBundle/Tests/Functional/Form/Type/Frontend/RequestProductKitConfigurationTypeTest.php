<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Functional\Form\Type\Frontend;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductKitConfigurationType;
use Symfony\Component\Validator\Constraints\Type;

class RequestProductKitConfigurationTypeTest extends FrontendWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroRFPBundle/Tests/Functional/Form/Type/Frontend/DataFixtures/RequestProductKitConfigurationType.yml',
        ]);

        $this->setCurrentWebsite('default');
    }

    public function testCreateWhenNoData(): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(RequestProductKitConfigurationType::class, null, ['csrf_protection' => false]);

        self::assertArrayIntersectEquals(
            [
                'data_class' => RequestProduct::class,
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('index'));
        self::assertArrayIntersectEquals(
            [
                'mapped' => false,
                'constraints' => [new Type(['type' => 'int'])],
            ],
            $form->get('index')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('product'));
        self::assertTrue($form->has('kitItemLineItems'));
        self::assertArrayIntersectEquals(
            [
                'entry_options' => ['set_default_data' => true],
            ],
            $form->get('kitItemLineItems')->getConfig()->getOptions()
        );
        self::assertCount(0, $form->get('kitItemLineItems'));
        self::assertTrue($form->has('quantity'));
        self::assertArrayIntersectEquals(
            [
                'mapped' => false,
                'required' => true,
                'input' => 'number',
                'html5' => false,
                'useInputTypeNumberValueFormat' => true,
                'default_data' => 1.0,
            ],
            $form->get('quantity')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('productUnit'));
        self::assertArrayIntersectEquals(
            [
                'mapped' => false,
                'required' => true,
                'product' => null,
                'compact' => false,
                'sell' => true,
            ],
            $form->get('productUnit')->getConfig()->getOptions()
        );

        $formView = $form->createView();
        self::assertContains('oro_rfp_frontend_request_product_kit_configuration', $formView->vars['block_prefixes']);
        self::assertEquals([], $formView['kitItemLineItems']->vars['product_prices']);
    }

    public function testCreateWhenProductSimple(): void
    {
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        $requestProduct = (new RequestProduct())
            ->setProduct($productSimple1);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitConfigurationType::class,
            $requestProduct,
            ['csrf_protection' => false]
        );

        self::assertArrayIntersectEquals(
            [
                'data_class' => RequestProduct::class,
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('index'));
        self::assertArrayIntersectEquals(
            [
                'mapped' => false,
                'constraints' => [new Type(['type' => 'int'])],
            ],
            $form->get('index')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('product'));
        self::assertTrue($form->has('kitItemLineItems'));
        self::assertArrayIntersectEquals(
            [
                'entry_options' => ['set_default_data' => true],
            ],
            $form->get('kitItemLineItems')->getConfig()->getOptions()
        );
        self::assertCount(0, $form->get('kitItemLineItems'));
        self::assertTrue($form->has('quantity'));
        self::assertArrayIntersectEquals(
            [
                'mapped' => false,
                'required' => true,
                'input' => 'number',
                'html5' => false,
                'useInputTypeNumberValueFormat' => true,
                'default_data' => 1.0,
            ],
            $form->get('quantity')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('productUnit'));
        self::assertArrayIntersectEquals(
            [
                'mapped' => false,
                'required' => true,
                'product' => $productSimple1,
                'compact' => false,
                'sell' => true,
            ],
            $form->get('productUnit')->getConfig()->getOptions()
        );

        $formView = $form->createView();
        $frontendProductPricesDataProvider = self::getContainer()
            ->get('oro_pricing.tests.provider.frontend_product_prices');
        self::assertEquals(
            $frontendProductPricesDataProvider->getAllPricesForProducts([$productSimple1]),
            $formView['kitItemLineItems']->vars['product_prices']
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateWhenProductKit(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');
        /** @var Product $productSimple3 */
        $productSimple3 = $this->getReference('product_simple3');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');

        $requestProduct = (new RequestProduct())
            ->setProduct($productKit1);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitConfigurationType::class,
            $requestProduct,
            ['csrf_protection' => false]
        );

        self::assertArrayIntersectEquals(
            [
                'data_class' => RequestProduct::class,
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('index'));
        self::assertArrayIntersectEquals(
            [
                'mapped' => false,
                'constraints' => [new Type(['type' => 'int'])],
            ],
            $form->get('index')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('product'));
        self::assertTrue($form->has('kitItemLineItems'));
        self::assertArrayIntersectEquals(
            [
                'entry_options' => ['set_default_data' => true],
            ],
            $form->get('kitItemLineItems')->getConfig()->getOptions()
        );
        self::assertCount(2, $form->get('kitItemLineItems'));

        self::assertTrue($form->get('kitItemLineItems')->has((string)$productKit1Item1->getId()));
        $kitItemLineItem1Form = $form->get('kitItemLineItems')->get((string)$productKit1Item1->getId());

        self::assertTrue($form->has('quantity'));
        self::assertArrayIntersectEquals(
            [
                'mapped' => false,
                'required' => true,
                'input' => 'number',
                'html5' => false,
                'useInputTypeNumberValueFormat' => true,
                'default_data' => 1.0,
            ],
            $form->get('quantity')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('productUnit'));
        self::assertArrayIntersectEquals(
            [
                'mapped' => false,
                'required' => true,
                'product' => $productKit1,
                'compact' => false,
                'sell' => true,
            ],
            $form->get('productUnit')->getConfig()->getOptions()
        );

        $kitItemLineItem1 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1);
        self::assertEquals($kitItemLineItem1, $kitItemLineItem1Form->getData());

        self::assertTrue($form->get('kitItemLineItems')->has((string)$productKit1Item2->getId()));
        $kitItemLineItem2Form = $form->get('kitItemLineItems')->get((string)$productKit1Item2->getId());

        $kitItemLineItem2 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2);
        self::assertEquals($kitItemLineItem2, $kitItemLineItem2Form->getData());

        $formView = $form->createView();
        $frontendProductPricesDataProvider = self::getContainer()
            ->get('oro_pricing.tests.provider.frontend_product_prices');
        self::assertEquals(
            $frontendProductPricesDataProvider->getAllPricesForProducts(
                [$productKit1, $productSimple1, $productSimple2, $productSimple3]
            ),
            $formView['kitItemLineItems']->vars['product_prices']
        );
    }

    public function testSubmitNewProductSimple(): void
    {
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitConfigurationType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            'product' => $productSimple1->getId(),
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $requestProduct = (new RequestProduct())
            ->setProduct($productSimple1);

        self::assertInstanceOf(RequestProduct::class, $form->getData());

        /** @var RequestProduct $actualRequestProduct */
        $actualRequestProduct = $form->getData();
        self::assertEquals($requestProduct->getProduct()->getId(), $actualRequestProduct->getProduct()->getId());
    }

    public function testSubmitExistingProductSimple(): void
    {
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');

        $requestProduct = (new RequestProduct())
            ->setProduct($productSimple1);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitConfigurationType::class,
            $requestProduct,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            'product' => $productSimple2->getId(),
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(RequestProduct::class, $form->getData());

        /** @var RequestProduct $actualRequestProduct */
        $actualRequestProduct = $form->getData();
        self::assertEquals($productSimple2->getId(), $actualRequestProduct->getProduct()->getId());
    }

    public function testSubmitNewProductKit(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');
        /** @var Product $productSimple1 */
        $productSimple3 = $this->getReference('product_simple3');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitConfigurationType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            'index' => 0,
            'product' => $productKit1->getId(),
            'kitItemLineItems' => [
                $productKit1Item1->getId() => [
                    'product' => $productSimple1->getId(),
                    'quantity' => 45.6789,
                ],
                $productKit1Item2->getId() => [
                    'product' => $productSimple3->getId(),
                    'quantity' => 42,
                ],
            ],
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $kitItemLineItem1 = (new RequestProductKitItemLineItem())
            ->setKitItem($productKit1Item1)
            ->setProduct($productSimple1)
            ->setQuantity(45.6789);
        $kitItemLineItem2 = (new RequestProductKitItemLineItem())
            ->setKitItem($productKit1Item2)
            ->setProduct($productSimple3)
            ->setQuantity(42);
        $requestProduct = (new RequestProduct())
            ->setProduct($productKit1)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        self::assertInstanceOf(RequestProduct::class, $form->getData());

        /** @var RequestProduct $actualRequestProduct */
        $actualRequestProduct = $form->getData();
        self::assertEquals($requestProduct->getProduct()->getId(), $actualRequestProduct->getProduct()->getId());

        self::assertCount(2, $actualRequestProduct->getKitItemLineItems());

        /** @var RequestProductKitItemLineItem $actualKitItemLineItem1 */
        $actualKitItemLineItem1 = $actualRequestProduct->getKitItemLineItems()->first();
        self::assertEquals($kitItemLineItem1->getKitItem()->getId(), $actualKitItemLineItem1->getKitItem()->getId());
        self::assertEquals(
            $kitItemLineItem1->getProduct()->getId(),
            $actualKitItemLineItem1->getProduct()->getId()
        );
        self::assertEquals($kitItemLineItem1->getQuantity(), $actualKitItemLineItem1->getQuantity());

        /** @var RequestProductKitItemLineItem $actualKitItemLineItem2 */
        $actualKitItemLineItem2 = $actualRequestProduct->getKitItemLineItems()->last();
        self::assertEquals($kitItemLineItem2->getKitItem()->getId(), $actualKitItemLineItem2->getKitItem()->getId());
        self::assertEquals(
            $kitItemLineItem2->getProduct()->getId(),
            $actualKitItemLineItem2->getProduct()->getId()
        );
        self::assertEquals($kitItemLineItem2->getQuantity(), $actualKitItemLineItem2->getQuantity());
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
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');
        /** @var Product $productSimple3 */
        $productSimple3 = $this->getReference('product_simple3');

        $requestProductItem = (new RequestProductItem())
            ->setQuantity(123)
            ->setProductUnit($productUnitEach)
            ->setPrice(Price::create(42.5678, 'USD'));
        $kitItemLineItem1 = (new RequestProductKitItemLineItem())
            ->setKitItem($productKit1Item1)
            ->setProduct($productSimple1)
            ->setQuantity(45.6789);
        $requestProduct = (new RequestProduct())
            ->setProduct($productKit1)
            ->addRequestProductItem($requestProductItem)
            ->addKitItemLineItem($kitItemLineItem1)
            ->setComment('Sample comment');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitConfigurationType::class,
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
                $productKit1Item2->getId() => [
                    'product' => $productSimple3->getId(),
                    'quantity' => 142,
                ],
            ],
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(RequestProduct::class, $form->getData());

        /** @var RequestProduct $actualRequestProduct */
        $actualRequestProduct = $form->getData();
        self::assertEquals($requestProduct->getProduct()->getId(), $actualRequestProduct->getProduct()->getId());

        self::assertCount(2, $actualRequestProduct->getKitItemLineItems());

        /** @var RequestProductKitItemLineItem $actualKitItemLineItem1 */
        $actualKitItemLineItem1 = $actualRequestProduct->getKitItemLineItems()->first();
        self::assertEquals(56.78, $actualKitItemLineItem1->getQuantity());
        self::assertEquals($productSimple2->getId(), $actualKitItemLineItem1->getProduct()->getId());

        /** @var RequestProductKitItemLineItem $actualKitItemLineItem2 */
        $actualKitItemLineItem2 = $actualRequestProduct->getKitItemLineItems()->last();
        self::assertEquals(142, $actualKitItemLineItem2->getQuantity());
        self::assertEquals($productSimple3->getId(), $actualKitItemLineItem2->getProduct()->getId());
    }
}
