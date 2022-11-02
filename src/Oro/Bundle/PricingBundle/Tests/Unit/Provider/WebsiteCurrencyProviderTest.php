<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;

class WebsiteCurrencyProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CurrencyProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $currencyProvider;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var WebsiteCurrencyProvider
     */
    protected $provider;

    protected function setUp(): void
    {
        $this->currencyProvider = $this->createMock(CurrencyProviderInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->provider = new WebsiteCurrencyProvider($this->currencyProvider, $this->doctrineHelper);
    }

    public function testGetWebsiteDefaultCurrency()
    {
        $this->currencyProvider->expects($this->atLeastOnce())->method('getDefaultCurrency')->willReturn('USD');
        $this->assertEquals('USD', $this->provider->getWebsiteDefaultCurrency(1));
        $this->assertEquals('USD', $this->provider->getWebsiteDefaultCurrency(2));
        $this->assertEquals('USD', $this->provider->getWebsiteDefaultCurrency(3));
    }

    public function testGetAllWebsitesCurrencies()
    {
        $this->currencyProvider->expects($this->atLeastOnce())->method('getDefaultCurrency')->willReturn('EUR');

        $repo = $this->createMock(WebsiteRepository::class);
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);

        $repo->expects($this->once())->method('getWebsiteIdentifiers')->willReturn([1, 2, 3, 4, 5]);

        $this->assertEquals(
            [1 => 'EUR', 2 => 'EUR', 3 => 'EUR', 4 => 'EUR', 5 => 'EUR'],
            $this->provider->getAllWebsitesCurrencies()
        );
    }
}
