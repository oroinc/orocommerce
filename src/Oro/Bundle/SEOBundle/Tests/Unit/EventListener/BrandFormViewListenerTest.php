<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\SEOBundle\EventListener\BrandFormViewListener;

class BrandFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /** @var BrandFormViewListener */
    protected $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new BrandFormViewListener($this->requestStack, $this->translator, $this->doctrineHelper);
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

        $this->listener->onBrandEdit($event);
    }
}
