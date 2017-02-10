<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Command\PriceListRecalculateCommand;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class PriceListRecalculateCommandTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadPriceListRelations::class,
            LoadProductPrices::class,
            LoadPriceListFallbackSettings::class,
        ]);
    }

    /**
     * @dataProvider commandDataProvider
     * @param $expectedMessage
     * @param array $params
     * @param int $expectedCount
     * @param array $websites
     * @param array $customerGroups
     * @param array $customers
     */
    public function testCommand(
        $expectedMessage,
        array $params,
        $expectedCount,
        array $websites = [],
        array $customerGroups = [],
        array $customers = []
    ) {
        $this->clearCombinedPrices();
        $this->assertCombinedPriceCount(0);

        $this->getContainer()->get('oro_pricing.builder.combined_price_list_builder')->resetCache();
        $this->getContainer()->get('oro_pricing.builder.website_combined_price_list_builder')->resetCache();
        $this->getContainer()->get('oro_pricing.builder.customer_group_combined_price_list_builder')->resetCache();
        $this->getContainer()->get('oro_pricing.builder.customer_combined_price_list_builder')->resetCache();
        $this->getContainer()->get('oro_pricing.resolver.combined_product_price_resolver')->resetCache();

        foreach ($websites as $websiteName) {
            $params[] = '--website='.$this->getReference($websiteName)->getId();
        }

        foreach ($customerGroups as $customerGroupName) {
            $params[] = '--customer-group='.$this->getReference($customerGroupName)->getId();
        }

        foreach ($customers as $customerName) {
            $params[] = '--customer='.$this->getReference($customerName)->getId();
        }

        $result = $this->runCommand(PriceListRecalculateCommand::NAME, $params);
        $this->assertContains($expectedMessage, $result);
        $this->assertCombinedPriceCount($expectedCount);
    }

    /**
     * @return array
     */
    public function commandDataProvider()
    {
        return [
            'all' => [
                'expected_message' => 'Start processing',
                'params' => ['--all'],
                'expectedCount' => 40 // 2 + 38 = config + website1
            ],
            'empty run' => [
                'expected_message' => 'ATTENTION',
                'params' => [],
                'expectedCount' => 0
            ],
            'website 1' => [
                'expected_message' => 'Start processing',
                'params' => [],
                'expectedCount' => 38,
                'website' => [LoadWebsiteData::WEBSITE1],
                'customerGroup' => [],
                'customer' => []
            ],
            'customer.level_1_1' => [
                'expected_message' => 'Start processing',
                'params' => [],
                'expectedCount' => 14,
                'website' => [],
                'customerGroup' => [],
                'customer' => ['customer.level_1_1']
            ],
            'customer.level_1.2' => [
                'expected_message' => 'Start processing',
                'params' => [],
                'expectedCount' => 4,
                'website' => [],
                'customerGroup' => [],
                'customer' => ['customer.level_1.2']
            ],
            'customer.level_1.3' => [
                'expected_message' => 'Start processing',
                'params' => [],
                'expectedCount' => 14,
                'website' => [],
                'customerGroup' => [],
                'customer' => ['customer.level_1.3']
            ],
            'customer_group' => [
                'expected_message' => 'Start processing',
                'params' => [],
                'expectedCount' => 24, // 6 + 4 + 14 = customer.level_1_1 + customer.level_1.2 + customer.level_1.3
                'website' => [],
                'customerGroup' => ['customer_group.group1'], // doesn't has own price list
                'customer' => []
            ],
        ];
    }

    /**
     * @param int $expectedCount
     */
    protected function assertCombinedPriceCount($expectedCount)
    {
        $combinedPrices = $this->getContainer()->get('doctrine')
            ->getRepository('OroPricingBundle:CombinedProductPrice')
            ->createQueryBuilder('a')->getQuery()->getResult();

        $this->assertCount($expectedCount, $combinedPrices);
    }

    protected function clearCombinedPrices()
    {
        $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroPricingBundle:CombinedProductPrice')
            ->getRepository('OroPricingBundle:CombinedProductPrice')
            ->createQueryBuilder('combinedProductPrice')
            ->delete('OroPricingBundle:CombinedProductPrice', 'combinedProductPrice')
            ->getQuery()
            ->execute();
    }
}
