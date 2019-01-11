<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Oro\Bundle\ConsentBundle\Provider\ConsentContextProvider;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;

class ConsentContextProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeManager;

    /**
     * @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteManager;

    /**
     * @var ConsentContextProvider
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->provider = new ConsentContextProvider(
            $this->scopeManager,
            $this->websiteManager
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->scopeManager,
            $this->provider
        );
    }

    public function testGetScope()
    {
        /** @var Scope $contentScope */
        $contentScope = $this->getEntity(Scope::class, ['id' => 123]);

        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with('web_content')
            ->willReturn($contentScope);

        $this->assertSame(
            $contentScope,
            $this->provider->getScope()
        );
    }

    public function testGetWebsiteByScope()
    {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $contentScope = new StubScope();
        $contentScope->setWebsite($website);

        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with('web_content')
            ->willReturn($contentScope);

        $this->assertSame(
            $website,
            $this->provider->getWebsite()
        );
    }

    public function testGetSetWebsite()
    {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $this->provider->setWebsite($website);

        $this->scopeManager->expects($this->never())
            ->method('findOrCreate');

        $this->assertSame(
            $website,
            $this->provider->getWebsite()
        );
    }

    public function testGetCurrentWebsite()
    {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $contentScope = new StubScope();
        $contentScope->setWebsite(null);

        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with('web_content')
            ->willReturn($contentScope);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->assertSame(
            $website,
            $this->provider->getWebsite()
        );
    }
}
