<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Functional;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

abstract class FrontendActionTestCase extends ActionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getActionExecutionRoute()
    {
        return 'orob2b_api_frontend_action_execute_actions';
    }

    /**
     * {@inheritdoc}
     */
    protected function getActionDialogRoute()
    {
        return 'orob2b_frontend_action_widget_form';
    }
}
