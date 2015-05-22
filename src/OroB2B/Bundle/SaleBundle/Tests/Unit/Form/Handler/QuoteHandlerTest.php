<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Handler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Form\Handler\QuoteHandler;

class QuoteHandlerTest extends FormHandlerTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->entity   = new Quote();
        $this->handler  = new QuoteHandler($this->form, $this->request, $this->manager);
    }
}
