<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\SEOBundle\EventListener\ContentNodeFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /**
     * @var ContentNodeFormViewListener
     */
    protected $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new ContentNodeFormViewListener($this->translator);
    }

    protected function tearDown()
    {
        unset($this->listener);
    }

    public function testOnContentNodeViewWithEmptyScrollData()
    {
        $page = new ContentNode();

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Twig_Environment $env */
        $env = $this->getEnvironmentForView($page, $this->listener->getMetaFieldLabelPrefix());

        $event = new BeforeListRenderEvent($env, new ScrollData(), $page);
        $this->listener->onContentNodeView($event);
    }

    public function testOnLandingContentNodeEdit()
    {
        $env = $this->getEnvironmentForEdit();
        $page = new ContentNode();
        $event = new BeforeListRenderEvent($env, new ScrollData(), $page);

        $this->listener->onContentNodeEdit($event);
    }
}
