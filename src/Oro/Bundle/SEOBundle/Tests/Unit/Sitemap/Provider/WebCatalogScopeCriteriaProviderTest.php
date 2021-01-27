<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SEOBundle\Sitemap\Provider\WebCatalogScopeCriteriaProvider;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Website\WebsiteInterface;

class WebCatalogScopeCriteriaProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeManager;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var WebCatalogScopeCriteriaProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->provider = new WebCatalogScopeCriteriaProvider(
            $this->configManager,
            $this->scopeManager,
            $this->registry
        );
    }

    public function testGetWebCatalogScopeForAnonymousCustomerGroup()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_web_catalog.web_catalog', false, false, $website],
                ['oro_customer.anonymous_customer_group', false, false, $website]
            )
            ->willReturnOnConsecutiveCalls(
                1,
                2
            );

        $webCatalog = $this->createMock(WebCatalog::class);
        $webCatalogEm = $this->createMock(EntityManagerInterface::class);
        $webCatalogEm->expects($this->once())
            ->method('find')
            ->with(WebCatalog::class, 1)
            ->willReturn($webCatalog);

        $customerGroup = $this->createMock(CustomerGroup::class);
        $customerGroupEm = $this->createMock(EntityManagerInterface::class);
        $customerGroupEm->expects($this->once())
            ->method('find')
            ->with(CustomerGroup::class, 2)
            ->willReturn($customerGroup);
        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->withConsecutive(
                [WebCatalog::class],
                [CustomerGroup::class]
            )
            ->willReturnOnConsecutiveCalls(
                $webCatalogEm,
                $customerGroupEm
            );

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with(
                'web_content',
                [
                    'website' => $website,
                    'webCatalog' => $webCatalog,
                    'customerGroup' => $customerGroup
                ]
            )
            ->willReturn($scopeCriteria);

        $this->assertEquals($scopeCriteria, $this->provider->getWebCatalogScopeForAnonymousCustomerGroup($website));
    }
}
