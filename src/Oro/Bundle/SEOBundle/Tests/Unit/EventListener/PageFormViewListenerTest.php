<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\SEOBundle\EventListener\PageFormViewListener;

class PageFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->listener = new PageFormViewListener($this->requestStack, $this->translator, $this->doctrineHelper);
    }

    public function testOnLandingPageView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $page = new Page();
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($page);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getEnvironmentForView($page);
        $event = $this->getEventForView($env);

        $this->listener->onPageView($event);
    }

    public function testOnLandingPageEdit()
    {
        $env = $this->getEnvironmentForEdit();
        $event = $this->getEventForEdit($env);

        $this->listener->onPageEdit($event);
    }
}
