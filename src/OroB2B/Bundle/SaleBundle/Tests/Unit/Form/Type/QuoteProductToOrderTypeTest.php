<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductToOrderType;

class QuoteProductToOrderTypeTest extends FormIntegrationTestCase
{
    /**
     * @var QuoteProductToOrderType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new QuoteProductToOrderType();
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteProductToOrderType::NAME, $this->type->getName());
    }
}
