<?php

namespace Oro\Bundle\FrontendBundle\Extension;

use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider;

use Oro\Bundle\WorkflowBundle\Extension\StartTransitionButtonProviderExtension;

class FrontendStartTransitionButtonProviderExtension extends StartTransitionButtonProviderExtension
{
    /**
     * {@inheritdoc}
     */
    protected function getApplication()
    {
        return ActionCurrentApplicationProvider::COMMERCE_APPLICATION;
    }
}
