<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntityChangesetTypeStub;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\CollectionSortOrderGridType;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class CollectionSortOrderGridTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    private FormFactoryInterface|MockObject $formFactory;

    private DoctrineHelper|MockObject $doctrineHelper;

    private CollectionSortOrderGridType $type;

    private EntityManager|MockObject $entityManager;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper
            ->method('getEntityManager')
            ->with(Product::class)
            ->willReturn($this->entityManager);

        $this->type = new CollectionSortOrderGridType($this->formFactory, $this->doctrineHelper);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                $this->type,
                EntityChangesetType::class => new EntityChangesetTypeStub()
            ], [])
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(EntityChangesetType::class, $this->type->getParent());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $options, mixed $submittedData, mixed $expectedData)
    {
        $form = $this->factory->create(CollectionSortOrderGridType::class, null, $options);

        if (!empty($expectedData)) {
            $this->entityManager
                ->expects(self::exactly(3))
                ->method('find')
                ->withConsecutive(
                    [Product::class, 1],
                    [Product::class, 2],
                    [Product::class, 3]
                )
                ->willReturnOnConsecutiveCalls(new ProductStub(), new ProductStub(), new ProductStub());
            if (null !== $options['segment']) {
                $collection = new CollectionSortOrder();
                $collection->setProduct(new ProductStub());
                $collection->setSegment(new Segment());
                $repo = $this->createMock(EntityRepository::class);
                $this->doctrineHelper->expects(self::exactly(3))
                    ->method('getEntityRepository')
                    ->with(CollectionSortOrder::class)
                    ->willReturn($repo);
                $repo->expects(self::exactly(3))
                    ->method('findOneBy')
                    ->withConsecutive(
                        [['product' => 1, 'segment' => null]],
                        [['product' => 2, 'segment' => null]],
                        [['product' => 3, 'segment' => null]]
                    )
                    ->willReturnOnConsecutiveCalls($collection);
            }
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        $segment = new Segment();
        $collectionSortOrder1 = new CollectionSortOrder();
        $collectionSortOrder1->setProduct(new ProductStub());
        $collectionSortOrder1->setSortOrder(1);
        $colSortOrderWithSegment1 = clone $collectionSortOrder1;
        $colSortOrderWithSegment1->setSegment($segment);
        $collectionSortOrder2 = new CollectionSortOrder();
        $collectionSortOrder2->setProduct(new ProductStub());
        $collectionSortOrder2->setSortOrder(0.2);
        $colSortOrderWithSegment2 = clone $collectionSortOrder2;
        $colSortOrderWithSegment2->setSegment($segment);
        $collectionSortOrder3 = new CollectionSortOrder();
        $collectionSortOrder3->setProduct(new ProductStub());
        $collectionSortOrder3->setSortOrder(null);
        $colSortOrderWithSegment3 = clone $collectionSortOrder3;
        $colSortOrderWithSegment3->setSegment($segment);

        return [
            'no data' => [
                'options' => ['segment' => null],
                'submittedData' => null,
                'expectedData' => [],
            ],
            'empty data' => [
                'options' => ['segment' => null],
                'submittedData' => '',
                'expectedData' => [],
            ],
            'valid data without segment' => [
                'options' => ['segment' => null],
                'submittedData' => json_encode([
                    '1' => ['categorySortOrder' => 1],
                    '2' => ['categorySortOrder' => 0.2],
                    '3' => ['categorySortOrder' => null],
                ], JSON_THROW_ON_ERROR),
                'expectedData' => [
                    '1' => [
                        'data' => $collectionSortOrder1,
                    ],
                    '2' => [
                        'data' => $collectionSortOrder2,
                    ],
                    '3' => [
                        'data' => $collectionSortOrder3,
                    ]
                ]
            ],
            'valid data with segment' => [
                'options' => ['segment' => $segment],
                'submittedData' => json_encode([
                    '1' => ['categorySortOrder' => 1],
                    '2' => ['categorySortOrder' => 0.2],
                    '3' => ['categorySortOrder' => null],
                ], JSON_THROW_ON_ERROR),
                'expectedData' => [
                    '1' => [
                        'data' => $colSortOrderWithSegment1,
                    ],
                    '2' => [
                        'data' => $colSortOrderWithSegment2,
                    ],
                    '3' => [
                        'data' => $colSortOrderWithSegment3,
                    ]
                ]
            ]
        ];
    }

    public function testFinishView()
    {
        $constraints = ['some' => 'constraints'];
        $constraintsView = new FormView();
        $constraintsView->vars['attr']['data-validation'] = json_encode($constraints, JSON_THROW_ON_ERROR);

        $constraintsForm = $this->createMock(FormInterface::class);
        $constraintsForm->expects($this->once())
            ->method('createView')
            ->willReturn($constraintsView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(NumberType::class, null, $this->isType('array'))
            ->willReturn($constraintsForm);

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);

        $this->type->finishView($view, $form, []);
        $this->assertArrayHasKey('sortOrderConstraints', $view->vars);
        $this->assertEquals($constraints, $view->vars['sortOrderConstraints']);
    }
}
