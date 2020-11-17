<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Model\PricingStorageSwitchHandler;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PricingStorageSwitchHandlerTest extends WebTestCase
{
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
        $this->configManager = $container->get('oro_config.global');
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
        $this->configManager->set('oro_pricing.default_price_lists', $this->currentPriceLists);
        $this->configManager->set('oro_pricing.default_price_list', $this->currentPriceList);
        $this->configManager->flush();
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

        $configManager = $this->getContainer()->get('oro_config.global');
        $this->assertSame([], $configManager->get('oro_pricing.default_price_lists'));
        $defaultPl = $em->getRepository(PriceList::class)->getDefault();
        $this->assertSame($defaultPl->getId(), $configManager->get('oro_pricing.default_price_list'));
    }

    public function testMoveAssociationsForCombinedPricingStorage()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(PriceList::class);
        $defaultPl = $em->getRepository(PriceList::class)->getDefault();
        $this->configManager->set('oro_pricing.default_price_list', $defaultPl->getId());

        $this->handler->moveAssociationsForCombinedPricingStorage();

        $configManager = $this->getContainer()->get('oro_config.global');
        $this->assertNull($configManager->get('oro_pricing.default_price_list'));
        $this->assertSame(
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

    /**
     * @param array $relations
     * @param string $priceListReference
     */
    protected function assertPriceListRelation(array $relations, string $priceListReference)
    {
        $this->assertCount(1, $relations);
        $relation = reset($relations);
        $this->assertSame(
            $this->getReference($priceListReference)->getId(),
            $relation->getPriceList()->getId()
        );
    }
}
