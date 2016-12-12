<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\SEOBundle\EventListener\ContentNodeFormViewListener;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /**
     * @var ContentNodeFormViewListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->listener = new ContentNodeFormViewListener(
            $this->requestStack,
            $this->translator,
            $this->doctrineHelper
        );
    }

    public function testOnContentNodeView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $page = new ContentNode();
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($page);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getEnvironmentForView($page);
        $event = $this->getEventForView($env);

        $this->listener->onContentNodeView($event);
    }

    public function testOnLandingContentNodeEdit()
    {
        $env = $this->getEnvironmentForEdit();
        $event = $this->getEventForEdit($env);

        $this->listener->onContentNodeEdit($event);
    }
}
