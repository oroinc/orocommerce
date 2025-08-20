<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Model\PricingStorageSwitchHandler;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PricingStorageSwitchHandlerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?int $initialPriceList;
    private ?array $initialPriceLists;
    private ConfigManager $configManager;
    private PricingStorageSwitchHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductPrices::class, LoadPriceListRelations::class]);

        $configConverter = self::getContainer()->get('oro_pricing.system_config_converter');

        $this->configManager = self::getConfigManager();
        $this->initialPriceList = $this->configManager->get('oro_pricing.default_price_list');
        $this->initialPriceLists = $configConverter->convertFromSaved(
            $this->configManager->get('oro_pricing.default_price_lists')
        );

        $this->handler = new PricingStorageSwitchHandler(
            $this->configManager,
            self::getContainer()->get('doctrine'),
            $configConverter
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->configManager->set('oro_pricing.default_price_list', $this->initialPriceList);
        $this->configManager->set('oro_pricing.default_price_lists', $this->initialPriceLists);
        $this->configManager->flush();
    }

    private function getFirstPriceList(): PriceList
    {
        return self::getContainer()->get('doctrine')->getRepository(PriceList::class)
            ->createQueryBuilder('p')
            ->orderBy('p.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
    }

    public function testMoveAssociationsForFlatPricingStorage(): void
    {
        $this->markTestSkipped('Due to BB-26112');
        $this->handler->moveAssociationsForFlatPricingStorage();

        $website = $this->getReference('US');
        $customerGroup = $this->getReference('customer_group.group1');
        $customer = $this->getReference('customer.level_1.3');

        $em = self::getContainer()->get('doctrine')->getManagerForClass(PriceList::class);
        $customerPlRelations = $em->getRepository(PriceListToCustomer::class)
            ->findBy(['customer' => $customer, 'website' => $website]);
        $this->assertPriceListRelation($customerPlRelations, 'price_list_4');

        $customerGroupPlRelations = $em->getRepository(PriceListToCustomerGroup::class)
            ->findBy(['customerGroup' => $customerGroup, 'website' => $website]);
        $this->assertPriceListRelation($customerGroupPlRelations, 'price_list_5');

        self::assertEquals([], $this->configManager->get('oro_pricing.default_price_lists'));
        $defaultPl = $this->getFirstPriceList();
        self::assertEquals($defaultPl->getId(), $this->configManager->get('oro_pricing.default_price_list'));
    }

    public function testMoveAssociationsForCombinedPricingStorage(): void
    {
        $defaultPl = $this->getFirstPriceList();
        $this->configManager->set('oro_pricing.default_price_list', $defaultPl->getId());

        $this->handler->moveAssociationsForCombinedPricingStorage();

        self::assertNull($this->configManager->get('oro_pricing.default_price_list'));
        self::assertEquals(
            [
                ['priceList' => $defaultPl->getId(), 'sort_order' => 0, 'mergeAllowed' => true]
            ],
            $this->configManager->get('oro_pricing.default_price_lists')
        );
    }

    protected function assertPriceListRelation(array $relations, string $priceListReference): void
    {
        self::assertCount(1, $relations);
        $relation = reset($relations);
        self::assertEquals(
            $this->getReference($priceListReference)->getId(),
            $relation->getPriceList()->getId()
        );
    }
}
