<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\SEOBundle\EventListener\ContentNodeFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Symfony\Component\Form\FormView;

class ContentNodeFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /** @var ContentNodeFormViewListener */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new ContentNodeFormViewListener($this->translator);
    }

    public function testOnContentNodeViewWithEmptyScrollData()
    {
        $page = new ContentNode();
        $env = $this->getEnvironmentForView($page, $this->listener->getMetaFieldLabelPrefix());

        $event = new BeforeListRenderEvent($env, new ScrollData(), $page);

        $this->listener->onContentNodeView($event);
    }

    public function testOnLandingContentNodeEdit()
    {
        $env = $this->getEnvironmentForEdit();
        $page = new ContentNode();

        $event = new BeforeListRenderEvent($env, new ScrollData(), $page, new FormView());

        $this->listener->onContentNodeEdit($event);
    }
}
