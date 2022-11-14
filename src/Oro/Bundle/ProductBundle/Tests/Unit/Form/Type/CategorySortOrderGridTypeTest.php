<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;
use Oro\Bundle\ProductBundle\Form\Type\CategorySortOrderGridType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class CategorySortOrderGridTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    private CategorySortOrderGridType $type;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->type = new CategorySortOrderGridType($this->formFactory, $this->doctrineHelper);

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
                DataChangesetType::class => new DataChangesetType()
            ], [])
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(DataChangesetType::class, $this->type->getParent());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(mixed $submittedData, mixed $expectedData)
    {
        $form = $this->factory->create(CategorySortOrderGridType::class, null);
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'no data' => [
                'submittedData' => null,
                'expectedData' => new ArrayCollection([]),
            ],
            'empty data' => [
                'submittedData' => '',
                'expectedData' => new ArrayCollection([]),
            ],
            'valid data' => [
                'submittedData' => json_encode([
                    '1' => ['categorySortOrder' => 1],
                    '2' => ['categorySortOrder' => 0.2],
                    '3' => ['categorySortOrder' => null],
                ], JSON_THROW_ON_ERROR),
                'expectedData' => new ArrayCollection([
                    '1' => [
                        'data' => ['categorySortOrder' => 1],
                    ],
                    '2' => [
                        'data' => ['categorySortOrder' => 0.2],
                    ],
                    '3' => [
                        'data' => ['categorySortOrder' => null],
                    ]
                ])
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
