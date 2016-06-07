<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider;

use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

abstract class AbstractSubtotalProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->currencyManager = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
