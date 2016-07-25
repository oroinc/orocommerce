<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserSettings;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

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
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
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

    /**
     * @return AccountUser
     */
    protected function getCurrentUser()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAccountBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
    }

    /**
     * @return Website
     */
    protected function getCurrentWebsite()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->find(1);
    }
}
