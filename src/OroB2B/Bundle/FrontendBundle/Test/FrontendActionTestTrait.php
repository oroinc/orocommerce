<?php

namespace OroB2B\Bundle\FrontendBundle\Test;

use Oro\Bundle\ActionBundle\Test\ActionTestTrait;

trait FrontendActionTestTrait
{
    use ActionTestTrait;

    /**
     * @return string
     */
    protected function getActionExecutionRoute()
    {
        return 'orob2b_api_frontend_action_execute_actions';
    }

    /**
     * @return string
     */
    protected function getActionDialogRoute()
    {
        return 'orob2b_frontend_action_widget_form';
    }
}
