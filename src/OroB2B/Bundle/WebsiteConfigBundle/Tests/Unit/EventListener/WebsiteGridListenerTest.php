<?php

namespace OroB2B\Bundle\WebsiteConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\WebsiteConfigBundle\EventListener\WebsiteGridListener;

class WebsiteGridListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnBuildBefore()
    {
        $gridConfig = DatagridConfiguration::create(
            [
                'properties' => [],
                'actions' => []
            ]
        );

        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $dataGrid */
        $dataGrid = $this->getMock(DatagridInterface::class);

        $event = new BuildBefore($dataGrid, $gridConfig);
        $listener = new WebsiteGridListener();
        $listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'type'   => 'url',
                'route'  => 'orob2b_website_config',
                'params' => ['id']
            ],
            $gridConfig->offsetGetByPath('[properties][config_link]')
        );
        $this->assertEquals(
            [
                'type'         => 'navigate',
                'label'        => 'orob2b.website_config.grid.action.config',
                'link'         => 'config_link',
                'icon'         => 'cog',
                'acl_resource' => 'orob2b_website_update'
            ],
            $gridConfig->offsetGetByPath('[actions][config]')
        );
    }
}
