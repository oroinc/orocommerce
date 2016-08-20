<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TaxBundle\EventListener\AccountTaxCodeGridListener;

class AccountTaxCodeGridListenerTest extends AbstractTaxCodeGridListenerTest
{
    public function testOnBuildBefore()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'accounts-grid']);
        $gridConfig->offsetSetByPath('[source][query][from]', [['alias' => 'accounts']]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntityMetadataForClass')
            ->with('Oro\Bundle\TaxBundle\Entity\AbstractTaxCode')->willReturn($metadata);

        $metadata->expects($this->once())->method('getAssociationsByTargetClass')->with('\stdClass')
            ->willReturn(['stdClass' => ['fieldName' => 'accountGroups']]);

        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            'accountGroupTaxCodes.code AS accountGroupTaxCode'
                        ],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'Oro\Bundle\TaxBundle\Entity\AbstractTaxCode',
                                    'alias' => 'accountGroupTaxCodes',
                                    'conditionType' => 'WITH',
                                    'condition' => 'account_group MEMBER OF accountGroupTaxCodes.accountGroups'
                                ],
                            ],
                        ],
                        'from' => [['alias' => 'accounts']],
                    ],
                ],
                'columns' => [
                    'accountGroupTaxCode' => ['label' => 'oro.tax.taxcode.accountgroup.label', 'renderable' => false]
                ],
                'sorters' => [
                    'columns' => [
                        'accountGroupTaxCode' => ['data_name' => 'accountGroupTaxCode']
                    ]
                ],

                'filters' => [
                    'columns' => [
                        'accountGroupTaxCode' => ['data_name' => 'accountGroupTaxCode', 'type' => 'string'],
                    ]
                ],
                'name' => 'accounts-grid',
            ],
            $gridConfig->toArray()
        );
    }

    /**
     * @return AccountTaxCodeGridListener
     */
    protected function createListener()
    {
        return new AccountTaxCodeGridListener(
            $this->doctrineHelper,
            'Oro\Bundle\TaxBundle\Entity\AbstractTaxCode',
            '\stdClass'
        );
    }
}
