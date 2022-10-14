<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\SEOBundle\EventListener\PageFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;

class PageFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /** @var PageFormViewListener */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new PageFormViewListener($this->translator);
    }

    public function testOnLandingPageView()
    {
        $page = new Page();

        $env = $this->getEnvironmentForView($page, $this->listener->getMetaFieldLabelPrefix());
        $scrollData = new ScrollData();

        $event = new BeforeListRenderEvent($env, $scrollData, $page);

        $this->listener->onPageView($event);
    }

    public function testOnLandingPageEdit()
    {
        $page = new Page();

        $env = $this->getEnvironmentForEdit();
        $scrollData = new ScrollData();

        $event = new BeforeListRenderEvent($env, $scrollData, $page, new FormView());

        $this->listener->onPageEdit($event);
    }
}
