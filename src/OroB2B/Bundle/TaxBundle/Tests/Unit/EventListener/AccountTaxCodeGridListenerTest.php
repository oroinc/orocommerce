<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\EventListener\AccountTaxCodeGridListener;

class AccountTaxCodeGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AccountTaxCodeGridListener */
    protected $listener;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;


    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new AccountTaxCodeGridListener(
            $this->doctrineHelper,
            'OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode',
            '\stdClass',
            '\stdClass'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage for "\stdClass" not found in "OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode"
     */
    public function testOnBuildBeforeWithoutAssociation()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'std-grid']);
        $gridConfig->offsetSetByPath('[source][query][from]', [['alias' => 'std']]);
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with('OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode')
            ->willReturn($metadata);

        $this->listener->onBuildBefore($event);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage [source][query][from] is missing for grid "std-grid"
     */
    public function testOnBuildBeforeWithoutFromPart()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'std-grid']);
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->listener->onBuildBefore($event);
    }

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
                            'accountTaxCodes.code AS accountTaxCode',
                            'accountGroupTaxCodes.code AS accountGroupTaxCode'
                        ],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode',
                                    'alias' => 'accountTaxCodes',
                                    'conditionType' => 'WITH',
                                    'condition' => 'std MEMBER OF accountTaxCodes.stds',
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
                    'accountTaxCode' => ['label' => 'orob2b.tax.taxcode.label'],
                    'accountGroupTaxCode' => ['label' => 'orob2b.tax.taxcode.accountgroup.label', 'renderable' => false]
                ],
                'sorters' => [
                    'columns' => [
                        'accountTaxCode' => ['data_name' => 'accountTaxCode'],
                        'accountGroupTaxCode' => ['data_name' => 'accountGroupTaxCode']
                    ]
                ],

                'filters' => [
                    'columns' => [
                        'accountTaxCode' => ['data_name' => 'accountTaxCode', 'type' => 'string'],
                        'accountGroupTaxCode' => ['data_name' => 'accountGroupTaxCode', 'type' => 'string'],
                    ]
                ],
                'name' => 'std-grid',
            ],
            $gridConfig->toArray()
        );
    }
}
