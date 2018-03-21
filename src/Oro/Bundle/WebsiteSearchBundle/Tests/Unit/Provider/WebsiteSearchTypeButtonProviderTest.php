<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchTypeButtonProvider;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchTypeChainProvider;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\QueryString\QueryStringProvider;

class WebsiteSearchTypeButtonProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebsiteSearchTypeButtonProvider */
    protected $provider;

    /** @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $websiteManager;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var QueryStringProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $queryStringProvider;

    /** @var WebsiteSearchTypeChainProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $chainProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->queryStringProvider = $this->createMock(QueryStringProvider::class);
        $this->chainProvider = $this->createMock(WebsiteSearchTypeChainProvider::class);

        $this->provider = new WebsiteSearchTypeButtonProvider(
            $this->websiteManager,
            $this->configManager,
            $this->queryStringProvider,
            $this->chainProvider
        );
    }

    public function testGetAllAvailableSearchTypes(): void
    {
        $searchType = $this->prepareChainProvider(3);

        $this->assertEquals(
            $searchType,
            $this->provider->getAllAvailableSearchTypes()
        );
    }

    public function testIsWidgetVisibleShouldForMultipleTypes(): void
    {
        $this->prepareChainProvider(2);

        $this->assertEquals(
            true,
            $this->provider->isWidgetVisible()
        );
    }

    public function testIsWidgetVisibleShouldForSingleType(): void
    {
        $this->prepareChainProvider(1);

        $this->assertEquals(
            false,
            $this->provider->isWidgetVisible()
        );
    }

    /**
     * @param int $amount
     * @return array
     */
    private function prepareChainProvider($amount)
    {
        $searchTypes = array_fill(0, $amount, $this->createMock(WebsiteSearchTypeInterface::class));

        $this->chainProvider
            ->expects($this->once())
            ->method('getSearchTypes')
            ->willReturn($searchTypes);

        return $searchTypes;
    }
}
