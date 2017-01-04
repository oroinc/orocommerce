<?php
namespace Oro\Bundle\FrontendBundle\Tests\Unit\EventListener;

use Oro\Bundle\FrontendBundle\Extension\FrontendStartTransitionButtonProviderExtension;
use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider;

use Oro\Bundle\WorkflowBundle\Tests\Unit\Extension\StartTransitionButtonProviderExtensionTest;

class FrontendStartTransitionButtonProviderExtensionTest extends StartTransitionButtonProviderExtensionTest
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
