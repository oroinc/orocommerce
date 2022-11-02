<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @group CommunityEdition
 */
class AjaxEntityTotalsControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadShoppingListLineItems::class,
            LoadCombinedProductPrices::class,
        ]);
    }

    public function testEntityTotalsActionForShoppingList()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        // set customer user not default currency
        $manager = self::getConfigManager('global');
        $manager->set(CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_DEFAULT_CURRENCY), 'EUR');

        $user = $this->getCurrentUser();
        $website = $this->getCurrentWebsite();
        $settings = new CustomerUserSettings($website);
        $settings->setCurrency('EUR');
        $user->setWebsiteSettings($settings);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->persist($settings);
        $em->flush();

        $classNameHelper = $this->getContainer()->get('oro_entity.entity_class_name_helper');

        $params = [
            'entityClassName' => $classNameHelper->resolveEntityClass(ClassUtils::getClass($shoppingList)),
            'entityId' => $shoppingList->getId()
        ];

        $this->client->request('GET', $this->getUrl('oro_pricing_frontend_entity_totals', $params));

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = self::jsonToArray($result->getContent());

        $this->assertArrayHasKey('total', $data);
        $this->assertEquals(282.43, $data['total']['amount']);
        $this->assertEquals('EUR', $data['total']['currency']);

        $this->assertArrayHasKey('subtotals', $data);
        $this->assertEquals(282.43, $data['subtotals'][0]['amount']);
        $this->assertEquals('EUR', $data['subtotals'][0]['currency']);
    }

    public function testGetEntityTotalsAction()
    {
        $this->client->request('GET', $this->getUrl('oro_pricing_frontend_entity_totals'));
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testRecalculateTotalsAction()
    {
        $this->ajaxRequest('POST', $this->getUrl('oro_pricing_frontend_recalculate_entity_totals'));
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    private function getCurrentUser(): CustomerUser
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);
    }

    private function getCurrentWebsite(): Website
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(Website::class)
            ->find(1);
    }
}
