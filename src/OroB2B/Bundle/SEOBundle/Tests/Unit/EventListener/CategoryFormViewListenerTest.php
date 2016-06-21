<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\SEOBundle\EventListener\BaseFormViewListener;
use OroB2B\Bundle\SEOBundle\EventListener\CategoryFormViewListener;

class CategoryFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->listener = new CategoryFormViewListener($this->requestStack, $this->translator, $this->doctrineHelper);
    }


    public function testOnCategoryEdit()
    {
        $env = $this->getEnvironmentForEdit();
        $event = $this->getEventForEdit($env);

        $this->listener->onCategoryEdit($event);
    }
}
