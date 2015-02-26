<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Form\Handler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Form\Handler\CategoryHandler;

class CategoryHandlerTest extends FormHandlerTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->entity = new Category();
        $this->handler = new CategoryHandler($this->form, $this->request, $this->manager);
    }
}
