<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class PriceListTreeHandlerTest extends WebTestCase
{
    /**
     * @var PriceListTreeHandler
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadCombinedPriceLists::class]);

        $this->websiteManager = $this->getMockBuilder(WebsiteManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var $configManager ConfigManager */
        $this->configManager = $this->getContainer()->get('oro_config.global');

        $this->handler = new PriceListTreeHandler(
            $this->getContainer()->get('doctrine'),
            $this->websiteManager,
            $this->configManager
        );
    }

    /**
     * @param string $accountReference
     * @param string $expectedPriceListReference
     *
     * @dataProvider accountUserDataProvider
     */
    public function testGetPriceList($accountReference, $expectedPriceListReference)
    {
        $accountUser = new AccountUser();
        $accountUser->setAccount($this->getAccount($accountReference));

        $this->websiteManager->expects($this->any())->method('getCurrentWebsite')
            ->willReturn($this->getReference(LoadWebsiteData::WEBSITE1));

        $this->assertEquals(
            $this->getReference($expectedPriceListReference)->getName(),
            $this->handler->getPriceList($accountUser->getAccount())->getName()
        );
    }

    /**
     * @param string $accountReference
     * @param string $configPriceListReference
     * @param string $expectedPriceListReference
     *
     * @dataProvider priceListFromConfigDataProvider
     */
    public function testGetPriceListFromConfig(
        $accountReference,
        $configPriceListReference,
        $expectedPriceListReference
    ) {
        $accountUser = new AccountUser();
        $accountUser->setAccount($this->getAccount($accountReference));
        $key = implode(
            ConfigManager::SECTION_MODEL_SEPARATOR,
            [OroPricingExtension::ALIAS, Configuration::COMBINED_PRICE_LIST]
        );
        $configPriceList = $this->getReference($configPriceListReference);
        $this->configManager->set($key, $configPriceList->getId());
        $this->websiteManager->expects($this->any())->method('getCurrentWebsite')
            ->willReturn($this->getReference(LoadWebsiteData::WEBSITE1));

        $this->assertEquals(
            $this->getReference($expectedPriceListReference)->getName(),
            $this->handler->getPriceList($accountUser->getAccount())->getName()
        );
    }

    /**
     * @return array
     */
    public function accountUserDataProvider()
    {
        return [
            'get PriceList from account' => ['account.level_1.2', '2t_3f_1t'],
            'get PriceList from group' => ['account.level_1.3', '1t_2t_3t'],
            'get PriceList from website' => ['account.level_1.2.1', '1t_2t_3t'],
            'get PriceList from config' => ['account.level_1.2.1', '1t_2t_3t'],
        ];
    }

    /**
     * @return array
     */
    public function priceListFromConfigDataProvider()
    {
        return [
            'get PriceList from account' => ['account.level_1.2', '1t_2t_3t', '2t_3f_1t'],
            'get PriceList from config' => ['account.level_1.2.1', '1t_2t_3t', '1t_2t_3t'],
        ];
    }

    /**
     * @param string $reference
     * @return Account
     */
    protected function getAccount($reference)
    {
        return $this->getReference($reference);
    }
}
