<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadGuestCombinedPriceLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class CombinedPriceListTreeHandlerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /** @var CombinedPriceListTreeHandler */
    private $handler;

    /** @var \PHPUnit\Framework\MockObject\MockObject|WebsiteManager */
    private $websiteManager;

    /** @var ConfigManager */
    private $configManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadCombinedPriceLists::class, LoadGuestCombinedPriceLists::class]);

        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->configManager = self::getConfigManager();

        $this->handler = new CombinedPriceListTreeHandler(
            $this->getContainer()->get('doctrine'),
            $this->websiteManager,
            self::getConfigManager(null)
        );
    }

    /**
     * @dataProvider customerUserDataProvider
     */
    public function testGetPriceList(string $customerReference, string $expectedPriceListReference)
    {
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($this->getCustomer($customerReference));

        $this->websiteManager->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($this->getReference(LoadWebsiteData::WEBSITE1));

        $this->assertEquals(
            $this->getReference($expectedPriceListReference)->getName(),
            $this->handler->getPriceList($customerUser->getCustomer())->getName()
        );
    }

    /**
     * @dataProvider priceListFromConfigDataProvider
     */
    public function testGetPriceListFromConfig(
        string $customerReference,
        string $configPriceListReference,
        string $expectedPriceListReference
    ) {
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($this->getCustomer($customerReference));
        $configPriceList = $this->getReference($configPriceListReference);
        $this->configManager->set('oro_pricing.combined_price_list', $configPriceList->getId());
        $this->websiteManager->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($this->getReference(LoadWebsiteData::WEBSITE1));

        $this->assertEquals(
            $this->getReference($expectedPriceListReference)->getName(),
            $this->handler->getPriceList($customerUser->getCustomer())->getName()
        );
    }

    public function customerUserDataProvider(): array
    {
        return [
            'get PriceList from customer' => ['customer.level_1.2', '2t_3f_1t'],
            'get PriceList from group' => ['customer.level_1.3', '1t_2t_3t'],
            'get PriceList from website' => ['customer.level_1.2.1', '1t_2t_3t'],
            'get PriceList from config' => ['customer.level_1.2.1', '1t_2t_3t'],
        ];
    }

    public function priceListFromConfigDataProvider(): array
    {
        return [
            'get PriceList from customer' => ['customer.level_1.2', '1t_2t_3t', '2t_3f_1t'],
            'get PriceList from config' => ['customer.level_1.2.1', '1t_2t_3t', '1t_2t_3t'],
        ];
    }

    private function getCustomer(string $reference): Customer
    {
        return $this->getReference($reference);
    }
}
