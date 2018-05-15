<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider;

use Oro\Bundle\MultiWebsiteBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;

abstract class AbstractSubtotalProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyManager;

    /**
     * @var WebsiteCurrencyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteCurrencyProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);
    }
}
