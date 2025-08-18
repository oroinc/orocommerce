<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
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

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        LoadShoppingLists::setCurrency('EUR');
        $this->loadFixtures([
            LoadShoppingListLineItems::class,
            LoadCombinedProductPrices::class,
        ]);
    }

    public function testEntityTotalsActionForShoppingList(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $user = $this->getCurrentUser();
        $website = $this->getCurrentWebsite();
        $settings = new CustomerUserSettings($website);
        $settings->setCurrency('EUR');
        $user->setWebsiteSettings($settings);
        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($settings);
        $em->flush();

        $classNameHelper = self::getContainer()->get('oro_entity.entity_class_name_helper');

        // set customer user not default currency
        $configManager = self::getConfigManager();
        $initialDefaultCurrency = $configManager->get('oro_currency.default_currency');
        $configManager->set('oro_currency.default_currency', 'EUR');
        $configManager->flush();
        try {
            $this->client->request('GET', $this->getUrl('oro_pricing_frontend_entity_totals', [
                'entityClassName' => $classNameHelper->resolveEntityClass(ClassUtils::getClass($shoppingList)),
                'entityId' => $shoppingList->getId()
            ]));
            $result = $this->client->getResponse();
        } finally {
            $configManager->set('oro_currency.default_currency', $initialDefaultCurrency);
            $configManager->flush();
        }

        self::assertJsonResponseStatusCodeEquals($result, 200);

        $data = self::jsonToArray($result->getContent());

        self::assertArrayHasKey('total', $data);
        self::assertEquals(282.43, $data['total']['amount']);
        self::assertEquals('EUR', $data['total']['currency']);

        self::assertArrayHasKey('subtotals', $data);
        self::assertEquals(282.43, $data['subtotals'][0]['amount']);
        self::assertEquals('EUR', $data['subtotals'][0]['currency']);
    }

    public function testGetEntityTotalsAction(): void
    {
        $this->client->request('GET', $this->getUrl('oro_pricing_frontend_entity_totals'));
        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testRecalculateTotalsAction(): void
    {
        $this->ajaxRequest('POST', $this->getUrl('oro_pricing_frontend_recalculate_entity_totals'));
        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 404);
    }

    private function getCurrentUser(): CustomerUser
    {
        return self::getContainer()->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);
    }

    private function getCurrentWebsite(): Website
    {
        return self::getContainer()->get('doctrine')
            ->getRepository(Website::class)
            ->find(1);
    }
}
