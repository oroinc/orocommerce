<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Config\DefaultCurrencyConfigProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\EventListener\OrganizationListener;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use Oro\Component\Testing\Unit\EntityTrait;

class OrganizationListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var DefaultCurrencyConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $currencyConfigProvider;

    /**
     * @var PriceListConfigConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configConverter;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    private OrganizationListener $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->currencyConfigProvider = $this->createMock(DefaultCurrencyConfigProvider::class);
        $this->configConverter = $this->createMock(PriceListConfigConverter::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new OrganizationListener(
            $this->doctrineHelper,
            $this->currencyConfigProvider,
            $this->configConverter,
            $this->configManager
        );
    }

    public function testOnCreateOrganizations(): void
    {
        /** @var Organization $organization1 */
        $organization1 = $this->getEntity(Organization::class, ['id' =>1, 'name' => 'org1']);

        /** @var Organization $organization1 */
        $organization2 = $this->getEntity(Organization::class, ['id' =>2, 'name' => 'org2']);

        $existingPriceList = new PriceList();

        $expectedPriceList1 = new PriceList();
        $expectedPriceList1->setActive(true);
        $expectedPriceList1->setName('org1 Price List');
        $expectedPriceList1->setOrganization($organization1);
        $expectedPriceList1->setCurrencies(['USD', 'UAH']);

        $expectedPriceList2 = new PriceList();
        $expectedPriceList2->setActive(true);
        $expectedPriceList2->setName('org2 Price List');
        $expectedPriceList2->setOrganization($organization2);
        $expectedPriceList2->setCurrencies(['USD', 'UAH']);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::exactly(2))
            ->method('persist')
            ->withConsecutive(
                [$expectedPriceList1],
                [$expectedPriceList2]
            );
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->currencyConfigProvider->expects(self::exactly(2))
            ->method('getCurrencyList')
            ->willReturn(['USD', 'UAH']);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_pricing.default_price_lists')
            ->willReturn([]);

        $this->configConverter->expects(self::once())
            ->method('convertFromSaved')
            ->willReturn([new PriceListConfig($existingPriceList, 100, true)]);

        $this->configManager->expects(self::once())
            ->method('set')
            ->with(
                'oro_pricing.default_price_lists',
                [
                    new PriceListConfig($existingPriceList, 100, true),
                    new PriceListConfig($expectedPriceList1, 100, true),
                    new PriceListConfig($expectedPriceList2, 100, true)
                ]
            );
        $this->configManager->expects(self::once())
            ->method('flush');

        $this->listener->prePersist($organization1);
        $this->listener->prePersist($organization2);
        $this->listener->postFlush();
    }
}
