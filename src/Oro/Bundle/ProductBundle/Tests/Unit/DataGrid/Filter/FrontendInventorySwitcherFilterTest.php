<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\Filter;

use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Provider\DictionaryEntityDataProvider;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\ProductBundle\DataGrid\Filter\FrontendInventorySwitcherFilter;
use Oro\Bundle\ProductBundle\DataGrid\Form\Type\FrontendInventorySwitcherFilterType;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;
use Oro\Component\Exception\UnexpectedTypeException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

final class FrontendInventorySwitcherFilterTest extends TestCase
{
    private FormFactoryInterface|MockObject $formFactory;

    private DictionaryEntityDataProvider|MockObject $dictionaryEntityDataProvider;

    private ConfigManager|MockObject $configManager;

    private FrontendInventorySwitcherFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->dictionaryEntityDataProvider = $this->createMock(DictionaryEntityDataProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->filter = new FrontendInventorySwitcherFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->dictionaryEntityDataProvider,
            $this->configManager
        );
    }

    public function testGetMetadata(): void
    {
        $entityClass = \stdClass::class;
        $ids = ['item1'];
        $initialValues = [['id' => 'item1', 'value' => 'item1', 'text' => 'Item 1']];

        $statuses = ['in_stock', 'out_of_stock'];
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(
                Configuration::INVENTORY_FILTER_IN_STOCK_STATUSES_FOR_SIMPLE_FILTER
            ))
            ->willReturn($statuses);

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => 'field', 'class' => $entityClass]);

        $childFormView = new FormView();
        $childFormView->vars['choices'] = [];

        $formView = new FormView();
        $formView->children['type'] = $childFormView;

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);
        $valueFormField = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with('value')
            ->willReturn($valueFormField);
        $valueFormField->expects(self::once())
            ->method('getData')
            ->willReturn($ids);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(
                FrontendInventorySwitcherFilterType::class,
                [],
                ['csrf_protection' => false, 'class' => $entityClass]
            )
            ->willReturn($form);

        $this->dictionaryEntityDataProvider->expects(self::once())
            ->method('getValuesByIds')
            ->with($entityClass, $ids)
            ->willReturn($initialValues);

        self::assertEquals(
            [
                'name' => 'test',
                'label' => 'Test',
                'choices' => [],
                'type' => 'inventory-switcher',
                'lazy' => false,
                'class' => $entityClass,
                'initialData' => $initialValues,
                'contextSearch' => false,
                'inStockStatuses' => $statuses,
            ],
            $this->filter->getMetadata()
        );
    }

    public function testGetMetadataWhenNoIds(): void
    {
        $entityClass = \stdClass::class;

        $statuses = ['in_stock', 'out_of_stock'];
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(
                Configuration::INVENTORY_FILTER_IN_STOCK_STATUSES_FOR_SIMPLE_FILTER
            ))
            ->willReturn($statuses);

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => 'field', 'class' => $entityClass]);

        $childFormView = new FormView();
        $childFormView->vars['choices'] = [];

        $formView = new FormView();
        $formView->children['type'] = $childFormView;

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);
        $valueFormField = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with('value')
            ->willReturn($valueFormField);
        $valueFormField->expects(self::once())
            ->method('getData')
            ->willReturn(null);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(
                FrontendInventorySwitcherFilterType::class,
                [],
                ['csrf_protection' => false, 'class' => $entityClass]
            )
            ->willReturn($form);

        $this->dictionaryEntityDataProvider->expects(self::never())
            ->method('getValuesByIds');

        self::assertEquals(
            [
                'name' => 'test',
                'label' => 'Test',
                'choices' => [],
                'type' => 'inventory-switcher',
                'lazy' => false,
                'class' => $entityClass,
                'initialData' => [],
                'contextSearch' => false,
                'inStockStatuses' => $statuses,
            ],
            $this->filter->getMetadata()
        );
    }

    public function testApplyExceptionForWrongFilterDatasourceAdapter(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->filter->apply($this->createMock(FilterDatasourceAdapterInterface::class), []);
    }

    public function testApply(): void
    {
        $fieldName = 'field_' . EnumIdPlaceholder::NAME;
        $value = [FrontendInventorySwitcherFilterType::TYPE_ENABLED];

        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);
        $ds->expects(self::once())
            ->method('addRestriction')
            ->with(
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison('field_in_stock', Comparison::EXISTS, null),
                        new Comparison('field_out_of_stock', Comparison::EXISTS, null),
                    ]
                )
            );

        $statuses = ['in_stock', 'out_of_stock'];
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->with(Configuration::getConfigKeyByName(
                Configuration::INVENTORY_FILTER_IN_STOCK_STATUSES_FOR_SIMPLE_FILTER
            ))
            ->willReturn($statuses);

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);

        self::assertTrue($this->filter->apply($ds, ['type' => null, 'value' => $value]));
    }

    public function testPrepareData(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $this->filter->prepareData([]);
    }
}
