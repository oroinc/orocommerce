<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ShoppingListBundle\EventListener\FrontendProductUnitDatagridListener;

class FrontendProductUnitDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FrontendProductUnitDatagridListener
     */
    protected $listener;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formatter;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formatter = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new FrontendProductUnitDatagridListener(
            $this->translator,
            $this->doctrineHelper,
            $this->formatter
        );
    }

    public function testOnBuildBefore()
    {
        $trans = 'unit';

        $this->translator->expects($this->any())
            ->method('trans')
            ->with('orob2b.shoppinglist.lineitem.unit.label')
            ->willReturn($trans);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals([
            'columns' => [
                FrontendProductUnitDatagridListener::PRODUCT_UNITS_COLUMN_NAME => [
                    'label' => $trans,
                    'frontend_type' => PropertyInterface::TYPE_ARRAY,
                ]
            ],
            'source' => ['query' => [
                'select' => [
                    sprintf(
                        'GROUP_CONCAT(shopping_list_form_unit.code SEPARATOR \'%s\') as shopping_list_form_units',
                        FrontendProductUnitDatagridListener::PRODUCT_UNITS_SEPARATOR
                    )
                ],
                'join' => ['left' => [
                    ['join' => 'product.unitPrecisions', 'alias' => 'shopping_list_form_unitPrecisions'],
                    ['join' => 'shopping_list_form_unitPrecisions.unit', 'alias' => 'shopping_list_form_unit'],
                ]],
            ]]
        ], $config->toArray());
    }

    /**
     * @dataProvider onResultAfterDataProvider
     * @param array $records
     * @param array $expectedRecords
     */
    public function testOnResultAfter(array $records, array $expectedRecords)
    {
        foreach ($expectedRecords as $index => $expectedRecord) {
            $this->formatter->expects($this->at($index))
                ->method('formatChoicesByCodes')
                ->with($expectedRecord['units'])
                ->willReturn($expectedRecord['units']);
        }

        /** @var DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new OrmResultAfter($datagrid, array_map(function ($record) {
            return new ResultRecord($record);
        }, $records));
        $this->listener->onResultAfter($event);
        $actualRecords = $event->getRecords();
        $this->assertSameSize($expectedRecords, $actualRecords);
        foreach ($expectedRecords as $expectedRecord) {
            $actualRecord = current($actualRecords);
            $this->assertEquals($expectedRecord['id'], $actualRecord->getValue('id'));
            $this->assertEquals($expectedRecord['units'], $actualRecord->getValue('units'));
            next($actualRecords);
        }
    }

    /**
     * @return array
     */
    public function onResultAfterDataProvider()
    {
        $separatorReplace = function ($string) {
            return str_replace(
                '{sep}',
                FrontendProductUnitDatagridListener::PRODUCT_UNITS_SEPARATOR,
                $string
            );
        };

        return [
            [
                'records' => [
                    ['id' => 1, 'units' => 'unit'],
                    ['id' => 2, 'units' => $separatorReplace('unit{sep}item')],
                    ['id' => 3, 'units' => $separatorReplace('box{sep}unit{sep}item')],
                ],
                'expectedRecords' => [
                    ['id' => 1, 'units' => ['unit']],
                    ['id' => 2, 'units' => ['unit', 'item']],
                    ['id' => 3, 'units' => ['box', 'unit', 'item']],
                ],
            ],
        ];
    }
}
