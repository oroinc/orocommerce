<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Functional\Form\Type\Frontend;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductItemCollectionType;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductItemType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RequestProductItemCollectionTypeTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroRFPBundle/Tests/Functional/Form/Type/Frontend/DataFixtures/RequestProductItemCollectionType.yml',
        ]);
    }

    public function testCreateWhenNoData(): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(RequestProductItemCollectionType::class, null, ['csrf_protection' => false]);

        self::assertCount(0, $form);
        self::assertArrayIntersectEquals(
            [
                'entry_type' => RequestProductItemType::class,
                'show_form_when_empty' => false,
                'error_bubbling' => false,
                'prototype_name' => '__namerequestproductitem__',
            ],
            $form->getConfig()->getOptions()
        );
    }

    public function testCreateWhenHasData(): void
    {
        $requestProductItem1 = new RequestProductItem();
        $requestProductItem2 = new RequestProductItem();

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductItemCollectionType::class,
            [$requestProductItem1, $requestProductItem2],
            ['csrf_protection' => false]
        );

        self::assertCount(2, $form);
        self::assertArrayIntersectEquals(
            [
                'entry_type' => RequestProductItemType::class,
                'show_form_when_empty' => false,
                'error_bubbling' => false,
                'prototype_name' => '__namerequestproductitem__',
            ],
            $form->getConfig()->getOptions()
        );
    }

    public function testSubmitNew(): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductItemCollectionType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        /** @var ProductUnit $productUnitItem */
        $productUnitItem = $this->getReference('item');
        /** @var ProductUnit $productUnitEach */
        $productUnitEach = $this->getReference('each');
        $form->submit([
            [
                'productUnit' => $productUnitItem->getCode(),
                'quantity' => 12.3456,
                'price' => ['value' => 1.2345, 'currency' => 'USD'],
            ],
            [
                'productUnit' => $productUnitEach->getCode(),
                'quantity' => 23.4567,
                'price' => ['value' => 2.3456, 'currency' => 'USD'],
            ],
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors());
        self::assertTrue($form->isSynchronized());

        self::assertIsIterable($form->getData());

        $actualCollection = $form->getData();
        self::assertCount(2, $actualCollection);
        self::assertEquals(
            [
                (new RequestProductItem())
                    ->setProductUnit($productUnitItem)
                    ->setQuantity(12.3456)
                    ->setPrice(Price::create(1.2345, 'USD')),
                (new RequestProductItem())
                    ->setProductUnit($productUnitEach)
                    ->setQuantity(23.4567)
                    ->setPrice(Price::create(2.3456, 'USD')),
            ],
            $actualCollection
        );
    }

    public function testSubmitExisting(): void
    {
        /** @var ProductUnit $productUnitItem */
        $productUnitItem = $this->getReference('item');
        /** @var ProductUnit $productUnitEach */
        $productUnitEach = $this->getReference('each');

        $collection = new ArrayCollection([
            (new RequestProductItem())
                ->setProductUnit($productUnitItem)
                ->setQuantity(12.3456)
                ->setPrice(Price::create(1.2345, 'USD')),
            (new RequestProductItem())
                ->setProductUnit($productUnitEach)
                ->setQuantity(23.4567)
                ->setPrice(Price::create(2.3456, 'USD')),
        ]);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductItemCollectionType::class,
            $collection,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            [
                'productUnit' => $productUnitItem->getCode(),
                'quantity' => 12.3456,
                'price' => ['value' => 1.2345, 'currency' => 'USD'],
            ],
            [
                'productUnit' => $productUnitEach->getCode(),
                'quantity' => 23.4567,
                'price' => ['value' => 2.3456, 'currency' => 'USD'],
            ],
            [
                'productUnit' => $productUnitEach->getCode(),
                'quantity' => 34.5678,
                'price' => ['value' => 3.4567, 'currency' => 'USD'],
            ],
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors());
        self::assertTrue($form->isSynchronized());

        self::assertIsIterable($form->getData());

        $actualCollection = $form->getData();
        self::assertCount(3, $actualCollection);
        self::assertEquals(
            new ArrayCollection([
                (new RequestProductItem())
                    ->setProductUnit($productUnitItem)
                    ->setQuantity(12.3456)
                    ->setPrice(Price::create(1.2345, 'USD')),
                (new RequestProductItem())
                    ->setProductUnit($productUnitEach)
                    ->setQuantity(23.4567)
                    ->setPrice(Price::create(2.3456, 'USD')),
                (new RequestProductItem())
                    ->setProductUnit($productUnitEach)
                    ->setQuantity(34.5678)
                    ->setPrice(Price::create(3.4567, 'USD')),
            ]),
            $actualCollection
        );
    }
}
