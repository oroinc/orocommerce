<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Functional\Form\Type\Frontend;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductKitItemLineItemCollectionType;

class RequestProductKitItemLineItemCollectionTypeTest extends FrontendWebTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped('will be unskipped in BB-23759');

        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroRFPBundle/Tests/Functional/Form/Type/Frontend/DataFixtures'
            . '/RequestProductKitItemLineItemCollectionType.yml',
        ]);

        $this->setCurrentWebsite('default');
    }

    public function testCreateWhenNoData(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitItemLineItemCollectionType::class,
            null,
            ['csrf_protection' => false, 'product' => $productKit1]
        );

        self::assertCount(2, $form);
        self::assertArrayIntersectEquals(
            [
                'by_reference' => false,
                'error_bubbling' => false,
                'product' => $productKit1,
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has((string)$productKit1Item1->getId()));
        $kitItemLineItem1Form = $form->get((string)$productKit1Item1->getId());

        self::assertArrayIntersectEquals(
            [
                'required' => !$productKit1Item1->isOptional(),
                'property_path' => '[' . $productKit1Item1->getId() . ']',
                'block_name' => 'entry',
                'product_kit_item' => $productKit1Item1,
            ],
            $kitItemLineItem1Form->getConfig()->getOptions()
        );

        $kitItemLineItem1 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1);
        self::assertEquals($kitItemLineItem1, $kitItemLineItem1Form->getData());

        self::assertTrue($form->has((string)$productKit1Item2->getId()));
        $kitItemLineItem2Form = $form->get((string)$productKit1Item2->getId());

        self::assertArrayIntersectEquals(
            [
                'required' => !$productKit1Item2->isOptional(),
                'property_path' => '[' . $productKit1Item2->getId() . ']',
                'block_name' => 'entry',
                'product_kit_item' => $productKit1Item2,
            ],
            $kitItemLineItem2Form->getConfig()->getOptions()
        );

        $kitItemLineItem2 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2);
        self::assertEquals($kitItemLineItem2, $kitItemLineItem2Form->getData());

        $formView = $form->createView();
        self::assertContains(
            'oro_rfp_frontend_request_product_kit_item_line_item_collection',
            $formView->vars['block_prefixes']
        );
    }

    public function testCreateWhenNoDataAndNotSetDefaultData(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitItemLineItemCollectionType::class,
            null,
            ['csrf_protection' => false, 'product' => $productKit1, 'entry_options' => ['set_default_data' => false]]
        );

        self::assertCount(2, $form);
        self::assertArrayIntersectEquals(
            [
                'by_reference' => false,
                'error_bubbling' => false,
                'product' => $productKit1,
                'entry_options' => ['set_default_data' => false],
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has((string)$productKit1Item1->getId()));
        $kitItemLineItem1Form = $form->get((string)$productKit1Item1->getId());

        self::assertArrayIntersectEquals(
            [
                'required' => !$productKit1Item1->isOptional(),
                'property_path' => '[' . $productKit1Item1->getId() . ']',
                'block_name' => 'entry',
                'product_kit_item' => $productKit1Item1,
                'set_default_data' => false,
            ],
            $kitItemLineItem1Form->getConfig()->getOptions()
        );

        self::assertNull($kitItemLineItem1Form->getData());

        self::assertTrue($form->has((string)$productKit1Item2->getId()));
        $kitItemLineItem2Form = $form->get((string)$productKit1Item2->getId());

        self::assertArrayIntersectEquals(
            [
                'required' => !$productKit1Item2->isOptional(),
                'property_path' => '[' . $productKit1Item2->getId() . ']',
                'block_name' => 'entry',
                'product_kit_item' => $productKit1Item2,
                'set_default_data' => false,
            ],
            $kitItemLineItem2Form->getConfig()->getOptions()
        );

        self::assertNull($kitItemLineItem2Form->getData());

        $formView = $form->createView();
        self::assertContains(
            'oro_rfp_frontend_request_product_kit_item_line_item_collection',
            $formView->vars['block_prefixes']
        );
    }

    public function testCreateWhenHasData(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');

        $kitItemLineItem1 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple2)
            ->setQuantity(12.3456);
        $kitItemLineItem2 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2)
            ->setQuantity(42);

        $collection = new ArrayCollection(
            [$productKit1Item1->getId() => $kitItemLineItem1, $productKit1Item2->getId() => $kitItemLineItem2]
        );

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitItemLineItemCollectionType::class,
            $collection,
            ['csrf_protection' => false, 'product' => $productKit1]
        );

        self::assertCount(2, $form);
        self::assertArrayIntersectEquals(
            [
                'by_reference' => false,
                'error_bubbling' => false,
                'product' => $productKit1,
                'entry_options' => [],
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has((string)$productKit1Item1->getId()));
        $kitItemLineItem1Form = $form->get((string)$productKit1Item1->getId());

        self::assertArrayIntersectEquals(
            [
                'required' => !$productKit1Item1->isOptional(),
                'property_path' => '[' . $productKit1Item1->getId() . ']',
                'block_name' => 'entry',
                'product_kit_item' => $productKit1Item1,
                'set_default_data' => true,
            ],
            $kitItemLineItem1Form->getConfig()->getOptions()
        );

        self::assertEquals($kitItemLineItem1, $kitItemLineItem1Form->getData());

        self::assertTrue($form->has((string)$productKit1Item2->getId()));
        $kitItemLineItem2Form = $form->get((string)$productKit1Item2->getId());

        self::assertArrayIntersectEquals(
            [
                'required' => !$productKit1Item2->isOptional(),
                'property_path' => '[' . $productKit1Item2->getId() . ']',
                'block_name' => 'entry',
                'product_kit_item' => $productKit1Item2,
                'set_default_data' => true,
            ],
            $kitItemLineItem2Form->getConfig()->getOptions()
        );

        self::assertEquals($kitItemLineItem2, $kitItemLineItem2Form->getData());
    }

    public function testSubmitNew(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');
        /** @var Product $productSimple3 */
        $productSimple3 = $this->getReference('product_simple3');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitItemLineItemCollectionType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false, 'product' => $productKit1]
        );

        $form->submit([
            $productKit1Item1->getId() => [
                'product' => $productSimple2->getId(),
                'quantity' => 12.3456,
            ],
            $productKit1Item2->getId() => [
                'product' => $productSimple3->getId(),
                'quantity' => 42,
            ],
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors());
        self::assertTrue($form->isSynchronized());

        $kitItemLineItem1 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple2)
            ->setQuantity(12.3456);
        $kitItemLineItem2 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2)
            ->setProduct($productSimple3)
            ->setQuantity(42);

        self::assertIsIterable($form->getData());

        $actualCollection = $form->getData();
        self::assertCount(2, $actualCollection);
        self::assertEquals(
            [
                $productKit1Item1->getId() => $kitItemLineItem1,
                $productKit1Item2->getId() => $kitItemLineItem2,
            ],
            $actualCollection
        );
    }

    public function testSubmitExisting(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        /** @var Product $productSimple2 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');
        /** @var Product $productSimple3 */
        $productSimple3 = $this->getReference('product_simple3');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');

        $kitItemLineItem1 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple2)
            ->setQuantity(12.3456);
        $kitItemLineItem2 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2)
            ->setQuantity(42);

        $collection = new ArrayCollection([
            $productKit1Item1->getId() => $kitItemLineItem1,
            $productKit1Item2->getId() => $kitItemLineItem2,
        ]);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitItemLineItemCollectionType::class,
            $collection,
            ['csrf_protection' => false, 'validation_groups' => false, 'product' => $productKit1]
        );

        $form->submit([
            $productKit1Item1->getId() => [
                'product' => $productSimple1->getId(),
                'quantity' => 34.5678,
            ],
            $productKit1Item2->getId() => [
                'product' => $productSimple3->getId(),
                'quantity' => 142,
            ],
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors());
        self::assertTrue($form->isSynchronized());

        $expectedKitItemLineItem1 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple1)
            ->setQuantity(34.5678);
        $expectedKitItemLineItem2 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2)
            ->setProduct($productSimple3)
            ->setQuantity(142);

        self::assertIsIterable($form->getData());

        $actualCollection = $form->getData();
        self::assertCount(2, $actualCollection);
        self::assertEquals(
            new ArrayCollection([
                $productKit1Item1->getId() => $expectedKitItemLineItem1,
                $productKit1Item2->getId() => $expectedKitItemLineItem2,
            ]),
            $actualCollection
        );
    }

    public function testSubmitShouldAddNonEmptyOptionalKitItemLineItem(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        /** @var Product $productSimple2 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');
        /** @var Product $productSimple3 */
        $productSimple3 = $this->getReference('product_simple3');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');

        $kitItemLineItem1 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple2)
            ->setQuantity(12.3456);

        $collection = new ArrayCollection([
            $productKit1Item1->getId() => $kitItemLineItem1,
        ]);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitItemLineItemCollectionType::class,
            $collection,
            ['csrf_protection' => false, 'validation_groups' => false, 'product' => $productKit1]
        );

        $form->submit([
            $productKit1Item1->getId() => [
                'product' => $productSimple1->getId(),
                'quantity' => 34.5678,
            ],
            $productKit1Item2->getId() => [
                'product' => $productSimple3->getId(),
                'quantity' => 42,
            ],
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors());
        self::assertTrue($form->isSynchronized());

        $expectedKitItemLineItem1 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple1)
            ->setQuantity(34.5678);

        $expectedKitItemLineItem2 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2)
            ->setProduct($productSimple3)
            ->setQuantity(42);

        self::assertIsIterable($form->getData());

        $actualCollection = $form->getData();
        self::assertCount(2, $actualCollection);
        self::assertEquals(
            new ArrayCollection([
                $productKit1Item1->getId() => $expectedKitItemLineItem1,
                $productKit1Item2->getId() => $expectedKitItemLineItem2,
            ]),
            $actualCollection
        );
    }

    public function testSubmitShouldDeleteEmptyOptionalKitItemLineItem(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        /** @var Product $productSimple2 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');

        $kitItemLineItem1 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple2)
            ->setQuantity(12.3456);
        $kitItemLineItem2 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2)
            ->setQuantity(42);

        $collection = new ArrayCollection([
            $productKit1Item1->getId() => $kitItemLineItem1,
            $productKit1Item2->getId() => $kitItemLineItem2,
        ]);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductKitItemLineItemCollectionType::class,
            $collection,
            ['csrf_protection' => false, 'validation_groups' => false, 'product' => $productKit1]
        );

        $form->submit([
            $productKit1Item1->getId() => [
                'product' => $productSimple1->getId(),
                'quantity' => 34.5678,
            ],
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors());
        self::assertTrue($form->isSynchronized());

        $expectedKitItemLineItem1 = self::getContainer()
            ->get('oro_rfp.product_kit.factory.request_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple1)
            ->setQuantity(34.5678);

        self::assertIsIterable($form->getData());

        $actualCollection = $form->getData();
        self::assertCount(1, $actualCollection);
        self::assertEquals(
            new ArrayCollection([
                $productKit1Item1->getId() => $expectedKitItemLineItem1,
            ]),
            $actualCollection
        );
    }
}
