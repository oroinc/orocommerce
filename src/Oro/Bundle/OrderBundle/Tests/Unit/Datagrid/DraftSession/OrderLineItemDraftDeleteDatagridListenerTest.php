<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid\DraftSession;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrderBundle\Datagrid\DraftSession\OrderLineItemDraftDeleteDatagridListener;
use PHPUnit\Framework\TestCase;

final class OrderLineItemDraftDeleteDatagridListenerTest extends TestCase
{
    private OrderLineItemDraftDeleteDatagridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new OrderLineItemDraftDeleteDatagridListener();
    }

    public function testOnBuildBeforeAddsProperty(): void
    {
        $orderId = 123;
        $draftSessionUuid = 'test-uuid-123';

        $parameters = new ParameterBag([
            'order_id' => $orderId,
            'draft_session_uuid' => $draftSessionUuid,
        ]);

        $config = DatagridConfiguration::create([]);
        $datagrid = new Datagrid('test-grid', $config, $parameters);
        $event = new BuildBefore($datagrid, $config);

        $this->listener->onBuildBefore($event);

        $properties = $config->offsetGetByPath('[properties]');

        self::assertIsArray($properties);
        self::assertArrayHasKey('oro_order_line_item_draft_delete', $properties);

        $property = $properties['oro_order_line_item_draft_delete'];
        self::assertEquals('url', $property['type']);
        self::assertEquals('oro_order_line_item_draft_delete', $property['route']);
        self::assertEquals(['orderLineItemId' => 'orderLineItemId'], $property['params']);
        self::assertEquals($orderId, $property['direct_params']['orderId']);
        self::assertEquals($draftSessionUuid, $property['direct_params']['orderDraftSessionUuid']);
    }

    public function testOnBuildBeforeWithNullOrderId(): void
    {
        $draftSessionUuid = 'test-uuid-456';

        $parameters = new ParameterBag([
            'draft_session_uuid' => $draftSessionUuid,
        ]);

        $config = DatagridConfiguration::create([]);
        $datagrid = new Datagrid('test-grid', $config, $parameters);
        $event = new BuildBefore($datagrid, $config);

        $this->listener->onBuildBefore($event);

        $properties = $config->offsetGetByPath('[properties]');
        $property = $properties['oro_order_line_item_draft_delete'];

        self::assertNull($property['direct_params']['orderId']);
        self::assertEquals($draftSessionUuid, $property['direct_params']['orderDraftSessionUuid']);
    }

    public function testOnBuildBeforeWithNullDraftSessionUuid(): void
    {
        $orderId = 789;

        $parameters = new ParameterBag([
            'order_id' => $orderId,
        ]);

        $config = DatagridConfiguration::create([]);
        $datagrid = new Datagrid('test-grid', $config, $parameters);
        $event = new BuildBefore($datagrid, $config);

        $this->listener->onBuildBefore($event);

        $properties = $config->offsetGetByPath('[properties]');
        $property = $properties['oro_order_line_item_draft_delete'];

        self::assertEquals($orderId, $property['direct_params']['orderId']);
        self::assertNull($property['direct_params']['orderDraftSessionUuid']);
    }

    public function testOnBuildBeforeWithEmptyParameters(): void
    {
        $parameters = new ParameterBag([]);

        $config = DatagridConfiguration::create([]);
        $datagrid = new Datagrid('test-grid', $config, $parameters);
        $event = new BuildBefore($datagrid, $config);

        $this->listener->onBuildBefore($event);

        $properties = $config->offsetGetByPath('[properties]');
        $property = $properties['oro_order_line_item_draft_delete'];

        self::assertNull($property['direct_params']['orderId']);
        self::assertNull($property['direct_params']['orderDraftSessionUuid']);
    }

    public function testOnBuildBeforeWithExistingProperties(): void
    {
        $orderId = 999;
        $draftSessionUuid = 'existing-uuid';

        $parameters = new ParameterBag([
            'order_id' => $orderId,
            'draft_session_uuid' => $draftSessionUuid,
        ]);

        $config = DatagridConfiguration::create([
            'properties' => [
                'existing_property' => [
                    'type' => 'field',
                    'frontend_type' => 'string',
                ],
            ],
        ]);

        $datagrid = new Datagrid('test-grid', $config, $parameters);
        $event = new BuildBefore($datagrid, $config);

        $this->listener->onBuildBefore($event);

        $properties = $config->offsetGetByPath('[properties]');

        self::assertArrayHasKey('existing_property', $properties);
        self::assertArrayHasKey('oro_order_line_item_draft_delete', $properties);
        self::assertCount(2, $properties);
    }
}
