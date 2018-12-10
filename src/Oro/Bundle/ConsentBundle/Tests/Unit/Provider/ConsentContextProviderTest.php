<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Oro\Bundle\ConsentBundle\Provider\ConsentContextProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

class ConsentContextProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeManager;

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

        $this->provider = new ConsentContextProvider(
            $this->scopeManager
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

    public function testGetSettedWebsite()
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
}
