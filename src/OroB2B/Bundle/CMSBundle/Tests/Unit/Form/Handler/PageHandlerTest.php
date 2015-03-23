<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Unit\Form\Handler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Bundle\CMSBundle\Form\Handler\PageHandler;

class PageHandlerTest extends FormHandlerTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->entity = new Page();
        $this->handler = new PageHandler($this->form, $this->request, $this->manager);
    }
}
