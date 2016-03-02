<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
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
            ->with('orob2b.product.productunit.entity_label')
            ->willReturn($trans);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals([
            'columns' => [
                'units' => [
                    'label' => $trans,
                    'frontend_type' => PropertyInterface::TYPE_ARRAY,
                ]
            ]
        ], $config->toArray());
    }
}
