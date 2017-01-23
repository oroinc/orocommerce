<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FrontendBundle\Extension\FrontendStartTransitionButtonProviderExtension;
use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider;

use Oro\Bundle\WorkflowBundle\Tests\Unit\Extension\StartTransitionButtonProviderExtensionTestCase;

class FrontendStartTransitionButtonProviderExtensionTestTest extends StartTransitionButtonProviderExtensionTestCase
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
        return new FrontendStartTransitionButtonProviderExtension($this->workflowRegistry, $this->routeProvider);
    }
}
