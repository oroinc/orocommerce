<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Entity\AccountUserSettings;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class AjaxEntityTotalsControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
            ]
        );
    }

    public function testEntityTotalsActionForShoppingList()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        // set account user not default currency
        $this->getContainer()->get('oro_config.manager')
            ->set(Configuration::getConfigKeyByName(Configuration::ENABLED_CURRENCIES), ['EUR', 'USD']);
        $user = $this->getCurrentUser();
        $website = $this->getCurrentWebsite();
        $settings = new AccountUserSettings($website);
        $settings->setCurrency('EUR');
        $user->setWebsiteSettings($settings);
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->persist($settings);
        $em->flush();


        $params = [
            'entityClassName' => ClassUtils::getClass($shoppingList),
            'entityId' => $shoppingList->getId()
        ];

        $this->client->request('GET', $this->getUrl('orob2b_pricing_frontend_entity_totals', $params));

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('total', $data);
        $this->assertEquals($data['total']['amount'], 282.43);
        $this->assertEquals($data['total']['currency'], 'EUR');

        $this->assertArrayHasKey('subtotals', $data);
        $this->assertEquals(282.43, $data['subtotals'][0]['amount']);
        $this->assertEquals('EUR', $data['subtotals'][0]['currency']);
    }

    public function testGetEntityTotalsAction()
    {
        $this->client->request('GET', $this->getUrl('orob2b_pricing_frontend_entity_totals'));
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testRecalculateTotalsAction()
    {
        $this->client->request('POST', $this->getUrl('orob2b_pricing_frontend_recalculate_entity_totals'));
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    /**
     * @return AccountUser
     */
    protected function getCurrentUser()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroAccountBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
    }

    /**
     * @return Website
     */
    protected function getCurrentWebsite()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroWebsiteBundle:Website')
            ->find(1);
    }
}
