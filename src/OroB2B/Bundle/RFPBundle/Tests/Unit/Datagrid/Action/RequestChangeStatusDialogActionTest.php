<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Unit\Datagrid\Action;

use OroB2B\Bundle\RFPBundle\Datagrid\Action\RequestChangeStatusDialogAction;

class RequestChangeStatusDialogActionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetOptions()
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $action = new RequestChangeStatusDialogAction($translator);
        $options = $action->getOptions();

        $this->assertInstanceOf('Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction', $action);
        $this->assertInstanceOf('Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration', $options);
        $this->assertCount(1, $options);
        $this->assertArrayHasKey('launcherOptions', $options);
    }
}