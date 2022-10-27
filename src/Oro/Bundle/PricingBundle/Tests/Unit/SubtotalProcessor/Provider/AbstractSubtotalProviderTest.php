<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;

abstract class AbstractSubtotalProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $currencyManager;

    /**
     * @var WebsiteCurrencyProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $websiteCurrencyProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);
    }
}
