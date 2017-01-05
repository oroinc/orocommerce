<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider;
use Oro\Bundle\FrontendBundle\Extension\FrontendTransitionButtonProviderExtension;

use Oro\Bundle\WorkflowBundle\Tests\Unit\Extension\TransitionButtonProviderExtensionTest;

class FrontendTransitionButtonProviderExtensionTest extends TransitionButtonProviderExtensionTest
{
    /**
     * {@inheritdoc}
     */
    protected function getApplication()
    {
        return ActionCurrentApplicationProvider::COMMERCE_APPLICATION;
    }

    /**
     * {@inheritdoc}
     */
    protected function createExtension()
    {
        return new FrontendTransitionButtonProviderExtension($this->workflowRegistry, $this->routeProvider);
    }
}
