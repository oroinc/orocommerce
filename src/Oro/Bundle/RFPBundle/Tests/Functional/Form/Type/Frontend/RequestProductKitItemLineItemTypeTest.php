<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Functional\Form\Type\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductKitItemLineItemType;

class RequestProductKitItemLineItemTypeTest extends FrontendWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroRFPBundle/Tests/Functional/Form/Type/Frontend/DataFixtures/RequestProductKitItemLineItemType.yml',
        ]);

        $this->setCurrentWebsite('default');
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
            RequestProductKitItemLineItemType::class,
            null,
            ['csrf_protection' => false, 'product_kit_item' => $productKit1Item1, 'required' => true]
        );

        self::assertArrayIntersectEquals(
            [
                'error_mapping' => ['.' => 'product'],
                'error_bubbling' => false,
                'data_class' => RequestProductKitItemLineItem::class,
                'set_default_data' => true,
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('product'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'choices' => [$productSimple1, $productSimple2],
                'choice_value' => 'id',
                'choice_translation_domain' => false,
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

        $kitItemLineItem = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1);
        $actualKitItemLineItem = $form->getData();
        self::assertEquals($kitItemLineItem, $actualKitItemLineItem);

        $formView = $form->createView();
        self::assertSame($productKit1Item1, $formView->vars['product_kit_item']);
        self::assertContains('oro_rfp_frontend_request_product_kit_item_line_item', $formView->vars['block_prefixes']);
    }

    public function testCreateWhenNoDataAndNotRequired(): void
    {
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');

        /** @var Product $productSimple3 */
        $productSimple3 = $this->getReference('product_simple3');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitItemLineItemType::class,
            null,
            ['csrf_protection' => false, 'product_kit_item' => $productKit1Item2, 'required' => false]
        );

        self::assertArrayIntersectEquals(
            [
                'error_mapping' => ['.' => 'product'],
                'error_bubbling' => false,
                'data_class' => RequestProductKitItemLineItem::class,
                'set_default_data' => true,
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('product'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'choices' => [$productSimple3, null],
                'choice_value' => 'id',
                'choice_translation_domain' => false,
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

        $kitItemLineItem = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2)
            ->setProduct(null)
            ->setOptional(true);
        $actualKitItemLineItem = $form->getData();
        self::assertEquals($kitItemLineItem, $actualKitItemLineItem);

        $formView = $form->createView();
        self::assertSame($productKit1Item2, $formView->vars['product_kit_item']);
    }

    public function testCreateWhenHasDataAndNotSetDefaultData(): void
    {
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');

        $kitItemLineItem = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct(null);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitItemLineItemType::class,
            $kitItemLineItem,
            [
                'csrf_protection' => false,
                'product_kit_item' => $productKit1Item1,
                'required' => false,
                'set_default_data' => false,
            ]
        );

        self::assertArrayIntersectEquals(
            [
                'error_mapping' => ['.' => 'product'],
                'error_bubbling' => false,
                'data_class' => RequestProductKitItemLineItem::class,
                'set_default_data' => false,
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('product'));
        self::assertArrayIntersectEquals(
            [
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'choices' => [$productSimple1, $productSimple2, null],
                'choice_value' => 'id',
                'choice_translation_domain' => false,
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

        $actualKitItemLineItem = $form->getData();
        self::assertEquals($kitItemLineItem, $actualKitItemLineItem);

        $formView = $form->createView();
        self::assertSame($productKit1Item1, $formView->vars['product_kit_item']);
    }

    public function testSubmitNew(): void
    {
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitItemLineItemType::class,
            null,
            [
                'csrf_protection' => false,
                'validation_groups' => false,
                'product_kit_item' => $productKit1Item1,
                'required' => true,
            ]
        );

        $form->submit([
            'product' => $productSimple1->getId(),
            'quantity' => 12.3456,
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(RequestProductKitItemLineItem::class, $form->getData());

        $kitItemLineItem = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setQuantity(12.3456)
            ->setOptional(false);

        self::assertEquals($kitItemLineItem, $form->getData());
    }

    public function testSubmitNoProduct(): void
    {
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitItemLineItemType::class,
            null,
            [
                'csrf_protection' => false,
                'validation_groups' => false,
                'product_kit_item' => $productKit1Item2,
                'required' => false,
            ]
        );

        $form->submit([
            'product' => '',
            'quantity' => 12.3456,
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(RequestProductKitItemLineItem::class, $form->getData());

        $kitItemLineItem = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2)
            ->setProduct(null)
            ->setQuantity(12.3456)
            ->setOptional(true);

        self::assertEquals($kitItemLineItem, $form->getData());
    }

    public function testSubmitExisting(): void
    {
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');

        $kitItemLineItem = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setQuantity(34.5678);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitItemLineItemType::class,
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

        self::assertInstanceOf(RequestProductKitItemLineItem::class, $form->getData());

        $expectedKitItemLineItem = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple2)
            ->setQuantity(12.3456);

        self::assertEquals($expectedKitItemLineItem, $form->getData());
    }
}
