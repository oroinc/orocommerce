<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Oro\Bundle\ConsentBundle\Provider\ConsentContextProvider;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;

class ConsentContextProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var ConsentContextProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->provider = new ConsentContextProvider(
            $this->scopeManager,
            $this->websiteManager
        );
    }

    public function testGetScope()
    {
        $contentScope = $this->getEntity(Scope::class, ['id' => 123]);

        $this->scopeManager->expects($this->once())
            ->method('findMostSuitable')
            ->with('web_content')
            ->willReturn($contentScope);

        $this->assertSame(
            $contentScope,
            $this->provider->getScope()
        );
    }

    public function testGetSetWebsite()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $this->provider->setWebsite($website);

        $this->scopeManager->expects($this->never())
            ->method('findMostSuitable');

        $this->assertSame(
            $website,
            $this->provider->getWebsite()
        );
    }

    public function testGetCurrentWebsite()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->assertSame(
            $website,
            $this->provider->getWebsite()
        );
    }

    public function testGetDefaultWebsite()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn(null);

        $this->websiteManager->expects($this->once())
            ->method('getDefaultWebsite')
            ->willReturn($website);

        $this->assertSame(
            $website,
            $this->provider->getWebsite()
        );
    }
}
