<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntityChangesetTypeStub;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\CollectionSortOrderGridType;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionSegmentProductsType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\CollectionSortOrderGridTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormError;

class ProductCollectionSegmentProductsTypeTest extends FormIntegrationTestCase
{
    private ProductCollectionSegmentProductsType $formType;

    protected function setUp(): void
    {
        $this->formType = new ProductCollectionSegmentProductsType();
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($entityManager);

        $this->entitiesToIdsTransformer = $this->createMock(EntitiesToIdsTransformer::class);
        $this->entityIdentifierType = $this->getMockBuilder(EntityIdentifierType::class)
            ->onlyMethods(['createEntitiesToIdsTransformer'])
            ->setConstructorArgs([$managerRegistry])
            ->getMock();

        $this->entityIdentifierType
            ->method('createEntitiesToIdsTransformer')
            ->willReturn($this->entitiesToIdsTransformer);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    ProductCollectionSegmentProductsType::class => $this->formType,
                    CollectionSortOrderGridType::class => new CollectionSortOrderGridTypeStub(),
                    EntityIdentifierType::class => $this->entityIdentifierType,
                    EntityChangesetType::class => new EntityChangesetTypeStub(),
                ],
                []
            )
        ];
    }

    public function testCreateFormDefault(): void
    {
        $form = $this->factory->create(ProductCollectionSegmentProductsType::class);

        $this->assertFormOptionEqual(false, 'csrf_protection', $form);
        $this->assertFormOptionEqual(null, 'segment', $form);

        self::assertTrue($form->has('appendProducts'));
        self::assertTrue($form->has('removeProducts'));
        self::assertTrue($form->has('sortOrder'));
    }

    public function testCreateFormWithSegment(): void
    {
        $segment = new Segment();
        $form = $this->factory->create(ProductCollectionSegmentProductsType::class, null, ['segment' => $segment]);

        $this->assertFormOptionEqual(false, 'csrf_protection', $form);
        $this->assertFormOptionEqual($segment, 'segment', $form);

        self::assertTrue($form->has('appendProducts'));
        self::assertTrue($form->has('removeProducts'));
        self::assertTrue($form->has('sortOrder'));
        $this->assertFormOptionEqual($segment, 'segment', $form->get('sortOrder'));
    }

    public function testSubmit(): void
    {
        $this->entitiesToIdsTransformer
            ->method('reverseTransform')
            ->willReturnCallback(
                fn (array $values) => array_map(fn (int $id) => $this->createProduct($id), $values)
            );

        $form = $this->factory->create(ProductCollectionSegmentProductsType::class);

        $form->submit([
            'appendProducts' => '10,20',
            'removeProducts' => '30,40',
            'sortOrder' => json_encode([10 => ['categorySortOrder' => 11]], JSON_THROW_ON_ERROR),
        ]);

        $this->assertFormIsValid($form);

        self::assertEquals(
            [$this->createProduct(10), $this->createProduct(20)],
            $form->get('appendProducts')->getData()
        );
        self::assertEquals(
            [$this->createProduct(30), $this->createProduct(40)],
            $form->get('removeProducts')->getData()
        );
        self::assertEquals(
            new ArrayCollection([10 => ['data' => ['categorySortOrder' => 11]]]),
            $form->get('sortOrder')->getData()
        );
    }

    public function testSubmitWhenInvalidProductsData(): void
    {
        $this->entitiesToIdsTransformer
            ->method('reverseTransform')
            ->willThrowException(new TransformationFailedException());

        $form = $this->factory->create(ProductCollectionSegmentProductsType::class);

        $form->submit([
            'appendProducts' => '10,20',
            'removeProducts' => '30,40',
        ]);

        $this->assertFormIsNotValid($form);

        self::assertEquals(
            [
                'oro.product.product_collection.append_products_invalid',
                'oro.product.product_collection.remove_products_invalid'
            ],
            array_map(static fn (FormError $error) => $error->getMessage(), iterator_to_array($form->getErrors()))
        );
    }

    public function testSubmitWhenInvalidSortOrderData(): void
    {
        $this->entitiesToIdsTransformer
            ->method('reverseTransform')
            ->willReturn([]);

        $form = $this->factory->create(ProductCollectionSegmentProductsType::class);

        $form->submit([
            'sortOrder' => 'invalid',
        ]);

        $this->assertFormIsNotValid($form);

        self::assertEquals(
            ['oro.product.product_collection.sort_order_invalid'],
            array_map(static fn (FormError $error) => $error->getMessage(), iterator_to_array($form->getErrors()))
        );
    }

    private function createProduct(int $id): ProductStub
    {
        return (new ProductStub())->setId($id);
    }
}
