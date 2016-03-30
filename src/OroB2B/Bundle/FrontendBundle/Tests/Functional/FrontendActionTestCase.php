<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Functional;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

abstract class FrontendActionTestCase extends ActionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getOperationExecutionRoute()
    {
        return 'orob2b_api_frontend_action_execute_operations';
    }

    /**
     * {@inheritdoc}
     */
    protected function getOperationDialogRoute()
    {
        return 'orob2b_frontend_action_widget_form';
    }
}
