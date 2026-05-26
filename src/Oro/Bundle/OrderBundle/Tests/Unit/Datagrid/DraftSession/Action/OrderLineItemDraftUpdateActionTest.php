<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid\DraftSession\Action;

use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\OrderBundle\Datagrid\DraftSession\Action\OrderLineItemDraftUpdateAction;
use PHPUnit\Framework\TestCase;

final class OrderLineItemDraftUpdateActionTest extends TestCase
{
    private OrderLineItemDraftUpdateAction $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->action = new OrderLineItemDraftUpdateAction();
    }

    public function testGetOptionsWithDefaultOptions(): void
    {
        $options = ActionConfiguration::create([
            'name' => 'test_action',
            'link' => '/test/link',
        ]);

        $this->action->setOptions($options);
        $result = $this->action->getOptions();

        self::assertEquals('test_action', $result->getName());
        self::assertEquals('/test/link', $result['link']);
        self::assertTrue($result['launcherOptions']['onClickReturnValue']);
        self::assertTrue($result['launcherOptions']['runAction']);
        self::assertEquals('no-hash', $result['launcherOptions']['className']);
        self::assertIsArray($result['launcherOptions']['widget']);
        self::assertIsArray($result['launcherOptions']['messages']);
    }

    public function testGetOptionsWithCustomLauncherOptions(): void
    {
        $options = ActionConfiguration::create([
            'name' => 'test_action',
            'link' => '/test/link',
            'launcherOptions' => [
                'onClickReturnValue' => false,
                'className' => 'custom-class',
                'customOption' => 'customValue',
            ],
        ]);

        $this->action->setOptions($options);
        $result = $this->action->getOptions();

        self::assertFalse($result['launcherOptions']['onClickReturnValue']);
        self::assertTrue($result['launcherOptions']['runAction']);
        self::assertEquals('custom-class', $result['launcherOptions']['className']);
        self::assertEquals('customValue', $result['launcherOptions']['customOption']);
        self::assertIsArray($result['launcherOptions']['widget']);
        self::assertIsArray($result['launcherOptions']['messages']);
    }

    public function testGetOptionsWithoutLinkThrowsException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('There is no option "link" for action "test_action".');

        $options = ActionConfiguration::create([
            'name' => 'test_action',
        ]);

        $this->action->setOptions($options);
    }

    public function testGetNameReturnsConfiguredName(): void
    {
        $options = ActionConfiguration::create([
            'name' => 'test_action',
            'link' => '/test/link',
        ]);

        $this->action->setOptions($options);

        self::assertEquals('test_action', $this->action->getName());
    }

    public function testGetAclResourceReturnsNull(): void
    {
        $options = ActionConfiguration::create([
            'name' => 'test_action',
            'link' => '/test/link',
        ]);

        $this->action->setOptions($options);

        self::assertNull($this->action->getAclResource());
    }

    public function testGetAclResourceReturnsConfiguredValue(): void
    {
        $options = ActionConfiguration::create([
            'name' => 'test_action',
            'link' => '/test/link',
            'acl_resource' => 'oro_order_update',
        ]);

        $this->action->setOptions($options);

        self::assertEquals('oro_order_update', $this->action->getAclResource());
    }

    public function testGetOptionsPreservesAdditionalOptions(): void
    {
        $options = ActionConfiguration::create([
            'name' => 'test_action',
            'link' => '/test/link',
            'icon' => 'fa-edit',
            'label' => 'Update',
            'rowAction' => false,
        ]);

        $this->action->setOptions($options);
        $result = $this->action->getOptions();

        self::assertEquals('fa-edit', $result['icon']);
        self::assertEquals('Update', $result['label']);
        self::assertFalse($result['rowAction']);
    }
}
