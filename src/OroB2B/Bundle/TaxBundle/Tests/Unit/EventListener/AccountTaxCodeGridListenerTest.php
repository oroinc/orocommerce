<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\TaxBundle\EventListener\AccountTaxCodeGridListener;

class AccountTaxCodeGridListenerTest extends AbstractTaxCodeGridListenerTest
{
    public function testOnBuildBefore()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'std-grid']);
        $gridConfig->offsetSetByPath('[source][query][from]', [['alias' => 'std']]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->exactly(2))->method('getEntityMetadataForClass')
            ->with('OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode')->willReturn($metadata);

        $metadata->expects($this->exactly(2))->method('getAssociationsByTargetClass')->with('\stdClass')
            ->willReturn(['stdClass' => ['fieldName' => 'stds']]);

        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            'taxCodes.code AS taxCode',
                            'accountGroupTaxCodes.code AS accountGroupTaxCode'
                        ],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode',
                                    'alias' => 'taxCodes',
                                    'conditionType' => 'WITH',
                                    'condition' => 'std MEMBER OF taxCodes.stds',
                                ],
                                [
                                    'join' => 'OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode',
                                    'alias' => 'accountGroupTaxCodes',
                                    'conditionType' => 'WITH',
                                    'condition' => 'std.group MEMBER OF accountGroupTaxCodes.stds',
                                ],
                            ],
                        ],
                        'from' => [['alias' => 'std']],
                    ],
                ],
                'columns' => [
                    'taxCode' => ['label' => 'orob2b.tax.taxcode.label'],
                    'accountGroupTaxCode' => ['label' => 'orob2b.tax.taxcode.accountgroup.label', 'renderable' => false]
                ],
                'sorters' => [
                    'columns' => [
                        'taxCode' => ['data_name' => 'taxCode'],
                        'accountGroupTaxCode' => ['data_name' => 'accountGroupTaxCode']
                    ]
                ],

                'filters' => [
                    'columns' => [
                        'taxCode' => ['data_name' => 'taxCode', 'type' => 'string'],
                        'accountGroupTaxCode' => ['data_name' => 'accountGroupTaxCode', 'type' => 'string'],
                    ]
                ],
                'name' => 'std-grid',
            ],
            $gridConfig->toArray()
        );
    }

    /**
     * @return AccountTaxCodeGridListener
     */
    protected function createListener()
    {
        $listener = new AccountTaxCodeGridListener(
            $this->doctrineHelper,
            'OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode',
            '\stdClass'
        );

        $listener->setRelatedAccountGroupClass('\stdClass');

        return $listener;
    }
}
