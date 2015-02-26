<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Handler\ProductHandler;

class ProductHandlerTest extends FormHandlerTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->entity = new Product();
        $this->handler = new ProductHandler($this->form, $this->request, $this->manager);
    }
}
