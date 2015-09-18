<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteToOrderType;

class QuoteToOrderTypeTest extends FormIntegrationTestCase
{
    /**
     * @var QuoteToOrderType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new QuoteToOrderType();
    }

    public function testGetParent()
    {
        $this->assertEquals('collection', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteToOrderType::NAME, $this->type->getName());
    }
}
