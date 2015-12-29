<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\TaxBundle\EventListener\TaxCodeGridListener;

class TaxCodeGridListenerTest extends AbstractTaxCodeGridListenerTest
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

        $this->doctrineHelper->expects($this->once())->method('getEntityMetadataForClass')
            ->with('OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode')->willReturn($metadata);

        $metadata->expects($this->once())->method('getAssociationsByTargetClass')->with('\stdClass')
            ->willReturn(['stdClass' => ['fieldName' => 'stds']]);

        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => ['taxCodes.code AS taxCode'],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode',
                                    'alias' => 'taxCodes',
                                    'conditionType' => 'WITH',
                                    'condition' => 'std MEMBER OF taxCodes.stds',
                                ],
                            ],
                        ],
                        'from' => [['alias' => 'std']],
                    ],
                ],
                'columns' => ['taxCode' => ['label' => 'orob2b.tax.taxcode.label']],
                'sorters' => ['columns' => ['taxCode' => ['data_name' => 'taxCode']]],
                'filters' => ['columns' => ['taxCode' => ['data_name' => 'taxCode', 'type' => 'string']]],
                'name' => 'std-grid',
            ],
            $gridConfig->toArray()
        );
    }

    /**
     * @return TaxCodeGridListener
     */
    protected function createListener()
    {
        return new TaxCodeGridListener(
            $this->doctrineHelper,
            'OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode',
            '\stdClass'
        );
    }
}
