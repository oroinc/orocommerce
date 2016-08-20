<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TaxBundle\EventListener\TaxCodeGridListener;

class TaxCodeGridListenerTest extends AbstractTaxCodeGridListenerTest
{
    public function testOnBuildBefore()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'accounts-grid']);
        $gridConfig->offsetSetByPath('[source][query][from]', [['alias' => 'account']]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntityMetadataForClass')
            ->with('Oro\Bundle\TaxBundle\Entity\AbstractTaxCode')->willReturn($metadata);

        $metadata->expects($this->once())->method('getAssociationsByTargetClass')->with('\stdClass')
            ->willReturn(['stdClass' => ['fieldName' => 'accounts']]);

        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => ['taxCodes.code AS taxCode'],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'Oro\Bundle\TaxBundle\Entity\AbstractTaxCode',
                                    'alias' => 'taxCodes',
                                    'conditionType' => 'WITH',
                                    'condition' => 'account MEMBER OF taxCodes.accounts',
                                ],
                            ],
                        ],
                        'from' => [['alias' => 'account']],
                    ],
                ],
                'columns' => ['taxCode' => ['label' => 'oro.tax.taxcode.label']],
                'sorters' => ['columns' => ['taxCode' => ['data_name' => 'taxCode']]],
                'filters' => ['columns' => ['taxCode' => ['data_name' => 'taxCode', 'type' => 'string']]],
                'name' => 'accounts-grid',
            ],
            $gridConfig->toArray()
        );
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


    /**
     * @return TaxCodeGridListener
     */
    protected function createListener()
    {
        return new TaxCodeGridListener(
            $this->doctrineHelper,
            'Oro\Bundle\TaxBundle\Entity\AbstractTaxCode',
            '\stdClass'
        );
    }
}
