<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

abstract class AbstractTaxCodeGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var object */
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

        $this->listener = $this->createListener();
    }

    protected function tearDown()
    {
        unset($this->listener, $this->doctrineHelper);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage for "\stdClass" not found in "Oro\Bundle\TaxBundle\Entity\AbstractTaxCode"
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
            ->with('Oro\Bundle\TaxBundle\Entity\AbstractTaxCode')
            ->willReturn($metadata);

        $this->listener->onBuildBefore($event);
    }

    /**
     * @return object
     */
    abstract protected function createListener();
}
