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
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SubcategoryFilterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var FilterUtility|\PHPUnit_Framework_MockObject_MockObject */
    protected $filterUtility;

    /** @var SubcategoryFilter */
    protected $filter;

    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filterUtility = $this->createMock(FilterUtility::class);
        $this->filterUtility->expects($this->any())
            ->method('getExcludeParams')
            ->willReturn([]);

        $this->filter = new SubcategoryFilter($this->formFactory, $this->filterUtility);
    }

    public function testInit()
    {
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                SearchEntityFilterType::class,
                [],
                [
                    'class' => Category::class,
                    'csrf_protection' => false,
                ]
            );

        $this->filter->init('filter', []);
        $this->filter->getForm();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid filter datasource adapter provided
     */
    public function testApplyExceptionForWrongFilterDatasourceAdapter()
    {
        /** @var FilterDatasourceAdapterInterface $datasource */
        $datasource = $this->createMock(FilterDatasourceAdapterInterface::class);

        $this->filter->apply($datasource, []);
    }

    public function testGetMetadata()
    {
        $category = $this->getCategory(42, '1_2');

        $this->filter->init(
            'test',
            [
                FilterUtility::DATA_NAME_KEY => 'field',
                'options' => [
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

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
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
                'name' => 'test',
                'label' => 'Test',
                'choices' => [
                    $category->getId() => new ChoiceView($category, $category->getId(), 'label'),
                ],
                'data_name' => 'field',
                'options' => [
                    'choices' => [$category],
                    'class' => Category::class,
                ],
                'lazy' => false,
            ],
            $this->filter->getMetadata()
        );
    }

    public function testApply()
    {
        $rootCategory = $this->getCategory(42, '1_42');
        $category1 = $this->getCategory(100, '1_42_100');
        $category2 = $this->getCategory(200, '1_42_200');

        $fieldName = 'field';
        $value = new ArrayCollection([$category1, $category2]);

        /** @var SearchFilterDatasourceAdapter|\PHPUnit_Framework_MockObject_MockObject $ds */
        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with(
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison('integer.field_1_42_100', Comparison::EXISTS, null),
                        new Comparison('integer.field_1_42_200', Comparison::EXISTS, null),
                    ]
                )
            );

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName, 'rootCategory' => $rootCategory]);

        $this->assertTrue(
            $this->filter->apply(
                $ds,
                [
                    'type' => null,
                    'value' => $value,
                ]
            )
        );
    }

    /**
     * @param int $id
     * @param string $path
     * @return Category
     */
    protected function getCategory($id, $path)
    {
        return $this->getEntity(Category::class, ['id' => $id, 'materializedPath' => $path]);
    }
}
