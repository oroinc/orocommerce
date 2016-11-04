<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\EventListener\ProductSearchQueryRestrictionEventListener;

class ProductSearchQueryRestrictionEventListenerTest extends AbstractProductSearchQueryRestrictionEventListenerTest
{
    public function testOnSearchQueryInFrontend()
    {
        $this->configureDependenciesForFrontend();

        $this->listener->onSearchQuery($this->getEvent());
    }

    public function testOnSearchQueryInBackend()
    {
        $this->configureDependenciesForBackend();

        $this->listener->onSearchQuery($this->getEvent());
    }

    /**
     * {@inheritdoc}
     */
    protected function createListener()
    {
        $listener = new ProductSearchQueryRestrictionEventListener(
            $this->configManager,
            $this->modifier,
            $this->frontendHelper,
            $this->frontendConfigPath
        );

        return $listener;
    }
}
