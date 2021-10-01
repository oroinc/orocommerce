<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Frontend;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\InventoryBundle\EventListener\Frontend\ProductDatagridUpcomingLabelListener;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class ProductDatagridUpcomingLabelListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductDatagridUpcomingLabelListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new ProductDatagridUpcomingLabelListener();
    }

    public function testOnPreBuild(): void
    {
        $config = DatagridConfiguration::createNamed('grid-name', []);
        $event = new PreBuild($config, new ParameterBag());

        $this->listener->onPreBuild($event);

        $this->assertEquals(
            [
                'name'       => 'grid-name',
                'source'     => [
                    'query' => [
                        'select' => [
                            'integer.is_upcoming as is_upcoming',
                            'datetime.availability_date as availability_date'
                        ]
                    ]
                ],
                'properties' => [
                    'is_upcoming'       => [
                        'type'          => 'field',
                        'frontend_type' => PropertyInterface::TYPE_BOOLEAN
                    ],
                    'availability_date' => [
                        'type'          => 'field',
                        'frontend_type' => PropertyInterface::TYPE_DATETIME
                    ],
                ],
            ],
            $config->toArray()
        );
    }

    public function testOnResultAfter(): void
    {
        $record1 = new ResultRecord(['availability_date' => new \DateTime()]);
        $record2 = new ResultRecord(['availability_date' => '']);
        $record3 = new ResultRecord([]);

        $event = new SearchResultAfter(
            $this->createMock(DatagridInterface::class),
            $this->createMock(SearchQueryInterface::class),
            [$record1, $record2, $record3]
        );
        $this->listener->onResultAfter($event);

        $this->assertInstanceOf(\DateTime::class, $record1->getValue('availability_date'));
        $this->assertNull($record2->getValue('availability_date'));
        $this->assertNull($record3->getValue('availability_date'));
    }
}
