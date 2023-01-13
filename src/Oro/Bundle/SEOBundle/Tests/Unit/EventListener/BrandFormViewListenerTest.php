<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\SEOBundle\EventListener\BrandFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;

class BrandFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /** @var BrandFormViewListener */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new BrandFormViewListener($this->translator);
    }

    public function testOnCategoryEdit()
    {
        $env = $this->getEnvironmentForEdit();
        $scrollData = new ScrollData();
        $brand = new Brand();

        $event = new BeforeListRenderEvent($env, $scrollData, $brand, new FormView());

        $this->listener->onBrandEdit($event);
    }
}
