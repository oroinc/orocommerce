<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\CatalogBundle\Datagrid\Filter\SubcategoryFilter;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEntityFilterType;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Component\Exception\UnexpectedTypeException;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SubcategoryFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var SubcategoryFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filter = new SubcategoryFilter($this->formFactory, new FilterUtility());
    }

    private function getCategory(int $id, string $path): Category
    {
        $category = new Category();
        ReflectionUtil::setId($category, $id);
        $category->setMaterializedPath($path);

        return $category;
    }

    public function testInit()
    {
        $form = $this->createMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                SearchEntityFilterType::class,
                [],
                ['csrf_protection' => false, 'class' => Category::class]
            )
            ->willReturn($form);

        $this->filter->init('filter', []);
        $this->assertSame($form, $this->filter->getForm());
    }

    public function testApplyExceptionForWrongFilterDatasourceAdapter()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->filter->apply($this->createMock(FilterDatasourceAdapterInterface::class), []);
    }

    public function testGetMetadata()
    {
        $category = $this->getCategory(42, '1_2');

        $this->filter->init(
            'test',
            [
                FilterUtility::DATA_NAME_KEY => 'field',
                'options'                    => [
                    'choices' => [$category]
                ]
            ]
        );

        $typeFormView = new FormView();
        $typeFormView->vars['choices'] = [];

        $valueFormView = new FormView();
        $valueFormView->vars['choices'] = [
            $category->getId() => new ChoiceView($category, $category->getId(), 'label'),
        ];

        $formView = new FormView();
        $formView->children['type'] = $typeFormView;
        $formView->children['value'] = $valueFormView;

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                SearchEntityFilterType::class,
                [],
                ['csrf_protection' => false, 'choices' => [$category], 'class' => Category::class]
            )
            ->willReturn($form);

        $this->assertEquals(
            [
                'name'    => 'test',
                'label'   => 'Test',
                'choices' => [
                    $category->getId() => new ChoiceView($category, $category->getId(), 'label'),
                ],
                'lazy'    => false,
            ],
            $this->filter->getMetadata()
        );
    }

    public function testApply()
    {
        $rootCategory = $this->getCategory(42, '1_42');
        $category1 = $this->getCategory(100, '1_42_100');
        $category2 = $this->getCategory(200, '1_42_200');

        $value = new ArrayCollection([$category1, $category2]);

        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);
        $ds->expects(self::once())
            ->method('addRestriction')
            ->with(
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison('integer.field.1_42_100', Comparison::EXISTS, null),
                        new Comparison('integer.field.1_42_200', Comparison::EXISTS, null),
                    ]
                )
            );

        $this->filter->init('test', [
            FilterUtility::DATA_NAME_KEY => 'field',
            'rootCategory'               => $rootCategory
        ]);

        self::assertTrue($this->filter->apply($ds, ['type' => null, 'value' => $value]));
    }

    public function testPrepareData()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->filter->prepareData([]);
    }
}
