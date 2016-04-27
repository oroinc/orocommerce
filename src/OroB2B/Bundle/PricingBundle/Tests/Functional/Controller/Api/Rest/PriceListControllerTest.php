<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;

/**
 * @dbIsolation
 */
class PriceListControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
    }

    public function testDelete()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $key = OroB2BPricingExtension::ALIAS
            . ConfigManager::SECTION_MODEL_SEPARATOR
            . Configuration::PRICE_LISTS_UPDATE_MODE;

        $configManager = $this->getContainer()->get('oro_config.scope.global');
        $defaultMode = $configManager->getSettingValue($key);
        $configManager->set($key, CombinedPriceListQueueConsumer::MODE_SCHEDULED);

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_pricing_delete_price_list', ['id' => $priceList->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $triggers = $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceListChangeTrigger')
            ->findBy(['account' => null, 'accountGroup' => null, 'website' => null, 'force' => true]);

        $this->assertCount(1, $triggers);

        $configManager->set($key, $defaultMode);
    }

    public function testDeleteDefault()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getRepository()->getDefault();

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_pricing_delete_price_list', ['id' => $priceList->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    /**
     * @return PriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceList');
    }
}
