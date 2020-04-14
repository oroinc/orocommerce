<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\SEOBundle\EventListener\BrandFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

class BrandFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /** @var BrandFormViewListener */
    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new BrandFormViewListener($this->translator);
    }

    protected function tearDown(): void
    {
        unset($this->listener);

        parent::tearDown();
    }

    public function testOnCategoryEdit()
    {
        $env = $this->getEnvironmentForEdit();
        $scrollData = new ScrollData();
        $brand = new Brand();

        $event = new BeforeListRenderEvent($env, $scrollData, $brand);

        $this->listener->onBrandEdit($event);
    }
}
