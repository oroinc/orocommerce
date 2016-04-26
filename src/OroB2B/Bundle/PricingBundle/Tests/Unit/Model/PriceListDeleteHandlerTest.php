<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model;

use OroB2B\Bundle\PricingBundle\Model\PriceListDeleteHandler;

class PriceListDeleteHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListDeleteHandler
     */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->handler = new PriceListDeleteHandler();
    }

    public function testHandle()
    {

    }
}
