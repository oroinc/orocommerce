<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\SEOBundle\EventListener\CategoryFormViewListener;

class CategoryFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /** @var CategoryFormViewListener */
    protected $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new CategoryFormViewListener($this->requestStack, $this->translator, $this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->listener);

        parent::tearDown();
    }

    public function testOnCategoryEdit()
    {
        $env = $this->getEnvironmentForEdit();
        $event = $this->getEventForEdit($env);

        $this->listener->onCategoryEdit($event);
    }
}
