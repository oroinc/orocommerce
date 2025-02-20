<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Functional\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductKitItemLineItemCollectionType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class QuoteProductKitItemLineItemCollectionTypeTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroSaleBundle/Tests/Functional/Form/Type/DataFixtures/QuoteProductKitItemLineItemCollectionType.yml',
        ]);

        $request = Request::createFromGlobals();
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        self::getClientInstance()->getContainer()->get('request_stack')->push($request);
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
            QuoteProductKitItemLineItemCollectionType::class,
            null,
            ['product' => $productKit1, 'csrf_protection' => false]
        );

        self::assertCount(2, $form);
        self::assertArrayIntersectEquals(
            [
                'by_reference' => false,
                'product' => $productKit1,
                'error_bubbling' => false,
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
                'currency' => null,
                'product_kit_item' => $productKit1Item1,
            ],
            $kitItemLineItem1Form->getConfig()->getOptions()
        );

        $kitItemLineItem1 = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1);
        self::assertEquals($kitItemLineItem1, $kitItemLineItem1Form->getData());

        self::assertTrue($form->has((string)$productKit1Item2->getId()));
        $kitItemLineItem2Form = $form->get((string)$productKit1Item2->getId());

        self::assertArrayIntersectEquals(
            [
                'required' => !$productKit1Item2->isOptional(),
                'property_path' => '[' . $productKit1Item2->getId() . ']',
                'block_name' => 'entry',
                'currency' => null,
                'product_kit_item' => $productKit1Item2,
            ],
            $kitItemLineItem2Form->getConfig()->getOptions()
        );

        $kitItemLineItem2 = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2);
        self::assertEquals($kitItemLineItem2, $kitItemLineItem2Form->getData());

        $formView = $form->createView();
        self::assertContains(
            'oro_sale_quote_product_kit_item_line_item_collection',
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
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple2)
            ->setQuantity(12.3456);
        $kitItemLineItem2 = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2)
            ->setQuantity(42);

        $collection = new ArrayCollection(
            [$productKit1Item1->getId() => $kitItemLineItem1, $productKit1Item2->getId() => $kitItemLineItem2]
        );

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductKitItemLineItemCollectionType::class,
            $collection,
            ['product' => $productKit1, 'csrf_protection' => false, 'currency' => 'USD']
        );

        self::assertCount(2, $form);
        self::assertArrayIntersectEquals(
            [
                'required' => !$productKit1Item1->isOptional(),
                'by_reference' => false,
                'product' => $productKit1,
                'error_bubbling' => false,
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
                'currency' => 'USD',
                'product_kit_item' => $productKit1Item1,
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
                'currency' => 'USD',
                'product_kit_item' => $productKit1Item2,
            ],
            $kitItemLineItem2Form->getConfig()->getOptions()
        );

        self::assertEquals($kitItemLineItem2, $kitItemLineItem2Form->getData());
    }

    public function testCreateShouldOverrideRequiredToFalseWhenPersistentCollection(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        /** @var Product $productSimple2 */
        $productSimple3 = $this->getReference('product_simple3');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');

        $kitItemLineItem2 = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2)
            ->setProduct($productSimple3)
            ->setQuantity(12.3456);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(ProductKitItem::class);
        $collection = new PersistentCollection(
            $entityManager,
            new ClassMetadata(ProductKitItem::class),
            new ArrayCollection([
                $productKit1Item2->getId() => $kitItemLineItem2,
            ])
        );

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductKitItemLineItemCollectionType::class,
            $collection,
            ['csrf_protection' => false, 'validation_groups' => false, 'product' => $productKit1]
        );

        self::assertTrue($form->has((string)$productKit1Item1->getId()));
        $kitItemLineItem1Form = $form->get((string)$productKit1Item1->getId());

        self::assertArrayIntersectEquals(
            [
                'required' => true,
            ],
            $kitItemLineItem1Form->getConfig()->getOptions()
        );
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
            QuoteProductKitItemLineItemCollectionType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false, 'product' => $productKit1, 'currency' => 'USD']
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
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple2)
            ->setQuantity(12.3456);
        $kitItemLineItem2 = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
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

        foreach ($form->all() as $child) {
            self::assertEquals('USD', $child->getConfig()->getOption('currency'));
        }
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
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple2)
            ->setQuantity(12.3456);
        $kitItemLineItem2 = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2)
            ->setQuantity(42);

        $collection = new ArrayCollection([
            $productKit1Item1->getId() => $kitItemLineItem1,
            $productKit1Item2->getId() => $kitItemLineItem2,
        ]);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductKitItemLineItemCollectionType::class,
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
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple1)
            ->setQuantity(34.5678);
        $expectedKitItemLineItem2 = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
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
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple2)
            ->setQuantity(12.3456);

        $collection = new ArrayCollection([
            $productKit1Item1->getId() => $kitItemLineItem1,
        ]);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductKitItemLineItemCollectionType::class,
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
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple1)
            ->setQuantity(34.5678);

        $expectedKitItemLineItem2 = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
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
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple2)
            ->setQuantity(12.3456);
        $kitItemLineItem2 = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item2)
            ->setQuantity(42);

        $collection = new ArrayCollection([
            $productKit1Item1->getId() => $kitItemLineItem1,
            $productKit1Item2->getId() => $kitItemLineItem2,
        ]);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductKitItemLineItemCollectionType::class,
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
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
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

    public function testSubmitWhenHasNotPersistentCollection(): void
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
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple2)
            ->setQuantity(12.3456);

        $collection = new ArrayCollection([
            $productKit1Item1->getId() => $kitItemLineItem1,
        ]);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteProductKitItemLineItemCollectionType::class,
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
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
            ->createKitItemLineItem($productKit1Item1)
            ->setProduct($productSimple1)
            ->setQuantity(34.5678);

        $expectedKitItemLineItem2 = self::getContainer()
            ->get('oro_sale.product_kit.factory.quote_product_kit_item_line_item')
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
}
