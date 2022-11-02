<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Handler\PriceListRelationHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceListRelationHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var PriceListRelationHandler */
    private $handler;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var WebsiteProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteProvider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject*/
    private $featureChecker;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->handler = new PriceListRelationHandler(
            $this->configManager,
            $this->doctrine,
            $this->websiteProvider
        );
        $this->assertFeatureChecker();
    }

    public function testIsPriceListFeatureDisabled(): void
    {
        $this->assertListenerStatus('any_feature');
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Only flat pricing engine is supported.');

        $this->handler->isPriceListAlreadyUsed($priceList);
    }


    public function testIsPriceListAlreadyUsedWithCustomerRelations(): void
    {
        $this->assertListenerStatus();
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $priceListToCustomerRepository = $this->createMock(PriceListToCustomerRepository::class);
        $priceListToCustomerRepository
            ->expects($this->once())
            ->method('hasRelationWithPriceList')
            ->with($priceList)
            ->willReturn(true);

        $this->doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(PriceListToCustomer::class)
            ->willReturn($priceListToCustomerRepository);

        $this->assertTrue($this->handler->isPriceListAlreadyUsed($priceList));
    }

    public function testIsPriceListAlreadyUsedWithCustomerGroupRelations(): void
    {
        $this->assertListenerStatus();
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $priceListToCustomerRepository = $this->createMock(PriceListToCustomerRepository::class);
        $priceListToCustomerRepository
            ->expects($this->once())
            ->method('hasRelationWithPriceList')
            ->with($priceList)
            ->willReturn(false);

        $priceListToCustomerGroupRepository = $this->createMock(PriceListToCustomerGroupRepository::class);
        $priceListToCustomerGroupRepository
            ->expects($this->once())
            ->method('hasRelationWithPriceList')
            ->with($priceList)
            ->willReturn(true);

        $this->doctrine
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [PriceListToCustomer::class, null, $priceListToCustomerRepository],
                [PriceListToCustomerGroup::class, null, $priceListToCustomerGroupRepository],
            ]);

        $this->assertTrue($this->handler->isPriceListAlreadyUsed($priceList));
    }

    public function testIsPriceListUsedInConfig(): void
    {
        $this->assertListenerStatus();
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 1]);

        $priceListToCustomerRepository = $this->createMock(PriceListToCustomerRepository::class);
        $priceListToCustomerRepository
            ->expects($this->once())
            ->method('hasRelationWithPriceList')
            ->with($priceList)
            ->willReturn(false);

        $priceListToCustomerGroupRepository = $this->createMock(PriceListToCustomerGroupRepository::class);
        $priceListToCustomerGroupRepository
            ->expects($this->once())
            ->method('hasRelationWithPriceList')
            ->with($priceList)
            ->willReturn(false);

        $this->doctrine
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [PriceListToCustomer::class, null, $priceListToCustomerRepository],
                [PriceListToCustomerGroup::class, null, $priceListToCustomerGroupRepository],
            ]);

        $this->websiteProvider
            ->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$website]);

        $this->configManager
            ->expects($this->once())
            ->method('getValues')
            ->with(Configuration::ROOT_NODE . '.' . Configuration::DEFAULT_PRICE_LIST, [$website], false, true)
            ->willReturn([['value' => $priceList->getId()]]);

        $this->assertTrue($this->handler->isPriceListAlreadyUsed($priceList));
    }

    private function assertFeatureChecker(): void
    {
        $this->featureChecker
            ->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturnCallback(fn (string $feature) => $feature == 'oro_price_lists_flat');

        $this->handler->setFeatureChecker($this->featureChecker);
    }

    private function assertListenerStatus(string $feature = 'oro_price_lists_flat'): void
    {
        $this->handler->addFeature($feature);
    }
}
