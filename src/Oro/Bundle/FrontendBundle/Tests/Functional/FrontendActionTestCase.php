<?php

namespace Oro\Bundle\FrontendBundle\Tests\Functional;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

abstract class FrontendActionTestCase extends ActionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getOperationExecutionRoute()
    {
        return 'oro_frontend_action_operation_execute';
    }

    /**
     * {@inheritdoc}
     */
    protected function getOperationDialogRoute()
    {
        return 'oro_frontend_action_widget_form';
    }
}
