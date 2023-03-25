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

    /**
     * @var PricingStorageSwitchHandler
     */
    private $handler;

    /**
     * @var array|null
     */
    private $currentPriceLists;

    /**
     * @var int|null
     */
    private $currentPriceList;

    /**
     * @var ConfigManager
     */
    private $configManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductPrices::class,
            LoadPriceListRelations::class
        ]);

        $container = $this->getContainer();
        $this->configManager = self::getConfigManager('global');
        $converter = $container->get('oro_pricing.system_config_converter');
        $this->currentPriceLists = $converter->convertFromSaved(
            $this->configManager->get('oro_pricing.default_price_lists')
        );
        $this->currentPriceList = $this->configManager->get('oro_pricing.default_price_list');

        $this->handler = new PricingStorageSwitchHandler(
            $this->configManager,
            $container->get('doctrine'),
            $converter
        );
    }

    protected function tearDown(): void
    {
        $configManager = self::getConfigManager('global');
        $configManager->set('oro_pricing.default_price_lists', $this->currentPriceLists);
        $configManager->set('oro_pricing.default_price_list', $this->currentPriceList);
        $configManager->flush();
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

    public function testMoveAssociationsForFlatPricingStorage()
    {
        $this->handler->moveAssociationsForFlatPricingStorage();

        $website = $this->getReference('US');
        $customerGroup = $this->getReference('customer_group.group1');
        $customer = $this->getReference('customer.level_1.3');

        $em = $this->getContainer()->get('doctrine')->getManagerForClass(PriceList::class);
        $customerPlRelations = $em->getRepository(PriceListToCustomer::class)
            ->findBy(['customer' => $customer, 'website' => $website]);
        $this->assertPriceListRelation($customerPlRelations, 'price_list_4');

        $customerGroupPlRelations = $em->getRepository(PriceListToCustomerGroup::class)
            ->findBy(['customerGroup' => $customerGroup, 'website' => $website]);
        $this->assertPriceListRelation($customerGroupPlRelations, 'price_list_5');

        $configManager = self::getConfigManager('global');
        $this->assertEquals([], $configManager->get('oro_pricing.default_price_lists'));
        $defaultPl = $this->getFirstPriceList();
        $this->assertEquals($defaultPl->getId(), $configManager->get('oro_pricing.default_price_list'));
    }

    public function testMoveAssociationsForCombinedPricingStorage()
    {
        $defaultPl = $this->getFirstPriceList();
        $this->configManager->set('oro_pricing.default_price_list', $defaultPl->getId());

        $this->handler->moveAssociationsForCombinedPricingStorage();

        $configManager = self::getConfigManager('global');
        $this->assertNull($configManager->get('oro_pricing.default_price_list'));
        $this->assertEquals(
            [
                [
                    'priceList' => $defaultPl->getId(),
                    'sort_order' => 0,
                    'mergeAllowed' => true
                ]
            ],
            $configManager->get('oro_pricing.default_price_lists')
        );
    }

    protected function assertPriceListRelation(array $relations, string $priceListReference)
    {
        $this->assertCount(1, $relations);
        $relation = reset($relations);
        $this->assertEquals(
            $this->getReference($priceListReference)->getId(),
            $relation->getPriceList()->getId()
        );
    }
}
