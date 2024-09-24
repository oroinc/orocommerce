<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Functional\Form\Type\Frontend;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductCollectionType;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RequestProductCollectionTypeTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroRFPBundle/Tests/Functional/Form/Type/Frontend/DataFixtures/RequestProductCollectionType.yml',
        ]);
    }

    public function testCreateWhenNoData(): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(RequestProductCollectionType::class, null, ['csrf_protection' => false]);

        self::assertCount(0, $form);
        self::assertArrayIntersectEquals(
            [
                'entry_type' => RequestProductType::class,
                'show_form_when_empty' => false,
                'error_bubbling' => false,
                'prototype_name' => '__namerequestproduct__',
            ],
            $form->getConfig()->getOptions()
        );
    }

    public function testCreateWhenHasData(): void
    {
        $requestProduct1 = new RequestProduct();
        $requestProduct2 = new RequestProduct();

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductCollectionType::class,
            [$requestProduct1, $requestProduct2],
            ['csrf_protection' => false]
        );

        self::assertCount(2, $form);
        self::assertArrayIntersectEquals(
            [
                'entry_type' => RequestProductType::class,
                'show_form_when_empty' => false,
                'error_bubbling' => false,
                'prototype_name' => '__namerequestproduct__',
            ],
            $form->getConfig()->getOptions()
        );
    }

    public function testSubmitNew(): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductCollectionType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');
        $form->submit([
            [
                'product' => $productSimple1->getId(),
                'comment' => 'Sample comment 1',
            ],
            [
                'product' => $productSimple2->getId(),
                'comment' => 'Sample comment 2',
            ],
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors());
        self::assertTrue($form->isSynchronized());

        self::assertIsIterable($form->getData());

        $actualCollection = $form->getData();
        self::assertCount(2, $actualCollection);
        self::assertEquals(
            [
                (new RequestProduct())
                    ->setProduct($productSimple1)
                    ->setComment('Sample comment 1'),
                (new RequestProduct())
                    ->setProduct($productSimple2)
                    ->setComment('Sample comment 2'),
            ],
            $actualCollection
        );
    }

    public function testSubmitExisting(): void
    {
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');

        $collection = new ArrayCollection([
            (new RequestProduct())
                ->setProduct($productSimple1)
                ->setComment('Sample comment 1'),
            (new RequestProduct())
                ->setProduct($productSimple2)
                ->setComment('Sample comment 2'),
        ]);

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestProductCollectionType::class,
            $collection,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit([
            [
                'product' => $productSimple2->getId(),
                'comment' => 'Updated comment 1',
            ],
            [
                'product' => $productSimple1->getId(),
                'comment' => 'Updated comment 2',
            ],
            [
                'product' => $productSimple2->getId(),
                'comment' => 'Updated comment 3',
            ],
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors());
        self::assertTrue($form->isSynchronized());

        self::assertIsIterable($form->getData());

        $actualCollection = $form->getData();
        self::assertCount(3, $actualCollection);
        self::assertEquals(
            new ArrayCollection([
                (new RequestProduct())
                    ->setProduct($productSimple2)
                    ->setComment('Updated comment 1'),
                (new RequestProduct())
                    ->setProduct($productSimple1)
                    ->setComment('Updated comment 2'),
                (new RequestProduct())
                    ->setProduct($productSimple2)
                    ->setComment('Updated comment 3'),
            ]),
            $actualCollection
        );
    }
}
