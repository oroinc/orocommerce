<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderProductKitItemLineItemCollectionType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class OrderProductKitItemLineItemCollectionTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;
    use OrderProductKitItemLineItemTypeTrait;

    private Product $productKit1;

    private Product $productKit2;

    private Product $kitItemProduct1;

    private Product $kitItemProduct2;

    private Product $kitItemProduct3;

    private Product $kitItemProduct4;

    private ProductKitItem $kitItem1;

    private ProductKitItem $kitItem2;

    private ProductKitItem $kitItem3;

    private ProductKitItem $kitItem4;

    private OrderProductKitItemLineItemCollectionType $formType;

    protected function setUp(): void
    {
        $this->kitItemProduct1 = (new ProductStub())->setId(300);
        $this->kitItemProduct2 = (new ProductStub())->setId(301);
        $this->kitItemProduct3 = (new ProductStub())->setId(302);
        $this->kitItemProduct4 = (new ProductStub())->setId(303);
        $this->kitItem1 = (new ProductKitItemStub(400))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct2));
        $this->kitItem2 = (new ProductKitItemStub(401))
            ->setOptional(true)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct3));
        $this->kitItem3 = (new ProductKitItemStub(402))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct4))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct3));
        $this->kitItem4 = (new ProductKitItemStub(403))
            ->setOptional(true)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct2));
        $this->productKit1 = (new ProductStub())
            ->setId(200)
            ->setType(Product::TYPE_KIT)
            ->addKitItem($this->kitItem1)
            ->addKitItem($this->kitItem2);
        $this->productKit2 = (new ProductStub())
            ->setId(201)
            ->setType(Product::TYPE_KIT)
            ->addKitItem($this->kitItem3)
            ->addKitItem($this->kitItem4);

        $this->formType = new OrderProductKitItemLineItemCollectionType();

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $kitItemProducts = [
            $this->kitItemProduct1->getId() => $this->kitItemProduct1,
            $this->kitItemProduct2->getId() => $this->kitItemProduct2,
            $this->kitItemProduct3->getId() => $this->kitItemProduct3,
            $this->kitItemProduct4->getId() => $this->kitItemProduct4,
        ];

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        $this->formType,
                        $this->getQuantityType(),
                        Select2EntityType::class => new EntityTypeStub($kitItemProducts),
                        $this->createOrderProductKitItemLineItemType($this, $kitItemProducts),
                    ],
                    []
                ),
            ]
        );
    }

    public function testBuildFormWhenNoDataAndNoProduct(): void
    {
        $form = $this->factory->create(OrderProductKitItemLineItemCollectionType::class, null, ['currency' => 'USD']);

        $this->assertFormOptionEqual(false, 'by_reference', $form);
        $this->assertFormOptionEqual('USD', 'currency', $form);
        $this->assertFormOptionEqual(null, 'product', $form);

        self::assertCount(0, $form);

        self::assertNull($form->getData());
    }

    public function testBuildFormWhenNoDataAndHasProduct(): void
    {
        $form = $this->factory->create(
            OrderProductKitItemLineItemCollectionType::class,
            null,
            ['product' => $this->productKit1, 'currency' => 'USD']
        );

        $this->assertFormOptionEqual(false, 'by_reference', $form);
        $this->assertFormOptionEqual('USD', 'currency', $form);

        $this->assertFormContainsField((string)$this->kitItem1->getId(), $form);
        $this->assertFormOptionEqual(true, 'required', $form->get((string)$this->kitItem1->getId()));
        $this->assertFormOptionEqual(
            '[' . $this->kitItem1->getId() . ']',
            'property_path',
            $form->get((string)$this->kitItem1->getId())
        );
        $this->assertFormOptionEqual('USD', 'currency', $form->get((string)$this->kitItem1->getId()));
        $this->assertFormOptionEqual('entry', 'block_name', $form->get((string)$this->kitItem1->getId()));

        $this->assertFormContainsField((string)$this->kitItem2->getId(), $form);
        $this->assertFormOptionEqual(false, 'required', $form->get((string)$this->kitItem2->getId()));
        $this->assertFormOptionEqual(
            '[' . $this->kitItem2->getId() . ']',
            'property_path',
            $form->get((string)$this->kitItem2->getId())
        );
        $this->assertFormOptionEqual('USD', 'currency', $form->get((string)$this->kitItem2->getId()));
        $this->assertFormOptionEqual('entry', 'block_name', $form->get((string)$this->kitItem2->getId()));

        self::assertCount(2, $form);

        self::assertNull($form->getData());
    }

    public function testBuildFormWhenHasData(): void
    {
        $collection = new ArrayCollection([
            $this->kitItem1->getId() => (new OrderProductKitItemLineItem())
                ->setKitItem($this->kitItem1)
                ->setProduct($this->kitItemProduct1)
                ->setQuantity(12.3456),
        ]);

        $form = $this->factory->create(
            OrderProductKitItemLineItemCollectionType::class,
            $collection,
            ['product' => $this->productKit1, 'currency' => 'USD']
        );

        $this->assertFormOptionEqual(false, 'by_reference', $form);
        $this->assertFormOptionEqual('USD', 'currency', $form);

        $this->assertFormContainsField((string)$this->kitItem1->getId(), $form);
        $this->assertFormOptionEqual(true, 'required', $form->get((string)$this->kitItem1->getId()));
        $this->assertFormOptionEqual(
            '[' . $this->kitItem1->getId() . ']',
            'property_path',
            $form->get((string)$this->kitItem1->getId())
        );
        $this->assertFormOptionEqual('USD', 'currency', $form->get((string)$this->kitItem1->getId()));
        $this->assertFormOptionEqual('entry', 'block_name', $form->get((string)$this->kitItem1->getId()));

        $this->assertFormContainsField((string)$this->kitItem2->getId(), $form);
        $this->assertFormOptionEqual(false, 'required', $form->get((string)$this->kitItem2->getId()));
        $this->assertFormOptionEqual(
            '[' . $this->kitItem2->getId() . ']',
            'property_path',
            $form->get((string)$this->kitItem2->getId())
        );
        $this->assertFormOptionEqual('USD', 'currency', $form->get((string)$this->kitItem2->getId()));
        $this->assertFormOptionEqual('entry', 'block_name', $form->get((string)$this->kitItem2->getId()));

        self::assertCount(2, $form);

        self::assertEquals($collection, $form->getData());
    }

    public function testSubmitWhenNoData(): void
    {
        $form = $this->factory->create(
            OrderProductKitItemLineItemCollectionType::class,
            null,
            ['product' => $this->productKit1, 'currency' => 'USD']
        );

        $data = [
            $this->kitItem1->getId() => [
                'product' => $this->kitItemProduct2->getId(),
                'quantity' => 1.2345,
            ],
            $this->kitItem2->getId() => [
                'product' => $this->kitItemProduct3->getId(),
                'quantity' => 2.3467,
            ],
        ];
        $form->submit($data);

        $this->assertFormIsValid($form);
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            [
                $this->kitItem1->getId() => (new OrderProductKitItemLineItem())
                    ->setKitItem($this->kitItem1)
                    ->setProduct($this->kitItemProduct2)
                    ->setQuantity(1.2345),
                $this->kitItem2->getId() => (new OrderProductKitItemLineItem())
                    ->setKitItem($this->kitItem2)
                    ->setProduct($this->kitItemProduct3)
                    ->setQuantity(2.3467),
            ],
            $form->getData()
        );
    }

    public function testSubmitWhenHasNotPersistentCollection(): void
    {
        $collection = new ArrayCollection([
            $this->kitItem1->getId() => (new OrderProductKitItemLineItem())
                ->setKitItem($this->kitItem1)
                ->setProduct($this->kitItemProduct2)
                ->setQuantity(1.2345),
            $this->kitItem2->getId() => (new OrderProductKitItemLineItem())
                ->setKitItem($this->kitItem2)
                ->setProduct($this->kitItemProduct3)
                ->setQuantity(2.3467),
        ]);
        $form = $this->factory->create(
            OrderProductKitItemLineItemCollectionType::class,
            $collection,
            ['product' => $this->productKit2, 'currency' => 'USD']
        );

        $data = [
            $this->kitItem3->getId() => [
                'product' => $this->kitItemProduct3->getId(),
                'quantity' => 2.3456,
            ],
            $this->kitItem4->getId() => [
                'product' => '',
                'quantity' => 0,
            ],
        ];
        $form->submit($data);

        $this->assertFormIsValid($form);
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            new ArrayCollection([
                $this->kitItem1->getId() => $collection[$this->kitItem1->getId()],
                $this->kitItem3->getId() => (new OrderProductKitItemLineItem())
                    ->setKitItem($this->kitItem3)
                    ->setProduct($this->kitItemProduct3)
                    ->setQuantity(2.3456)
                    ->setOptional(false),
            ]),
            $form->getData()
        );
    }

    public function testSubmitWhenHasPersistentCollection(): void
    {
        $kitItemLineItems = [
            $this->kitItem1->getId() => (new OrderProductKitItemLineItem())
                ->setKitItem($this->kitItem1)
                ->setProduct($this->kitItemProduct2)
                ->setQuantity(1.2345),
            $this->kitItem2->getId() => (new OrderProductKitItemLineItem())
                ->setKitItem($this->kitItem2)
                ->setProduct($this->kitItemProduct3)
                ->setQuantity(2.3467),
        ];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->createMock(UnitOfWork::class));
        $collection = new PersistentCollection(
            $entityManager,
            new ClassMetadata(OrderProductKitItemLineItem::class),
            new ArrayCollection($kitItemLineItems)
        );
        $form = $this->factory->create(
            OrderProductKitItemLineItemCollectionType::class,
            $collection,
            ['product' => $this->productKit2, 'currency' => 'USD']
        );

        $data = [
            $this->kitItem3->getId() => [
                'product' => $this->kitItemProduct3->getId(),
                'quantity' => 2.3456,
            ],
            $this->kitItem4->getId() => [
                'product' => '',
                'quantity' => 0,
            ],
        ];
        $form->submit($data);

        $this->assertFormIsValid($form);
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            [
                $this->kitItem1->getId() => $collection[$this->kitItem1->getId()],
                $this->kitItem3->getId() => (new OrderProductKitItemLineItem())
                    ->setKitItem($this->kitItem3)
                    ->setProduct($this->kitItemProduct3)
                    ->setQuantity(2.3456)
                    ->setOptional(true),
            ],
            $form->getData()->toArray()
        );
    }
}
