<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\SEOBundle\EventListener\CategoryFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;

class CategoryFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /** @var CategoryFormViewListener */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new CategoryFormViewListener($this->translator);
    }

    public function testOnCategoryEdit()
    {
        $env = $this->getEnvironmentForEdit();

        $event = new BeforeListRenderEvent($env, new ScrollData(), new \stdClass(), new FormView());

        $this->listener->onCategoryEdit($event);
    }
}
