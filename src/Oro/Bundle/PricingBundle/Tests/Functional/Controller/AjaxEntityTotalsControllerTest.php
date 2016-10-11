<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class AjaxEntityTotalsControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

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
        $classNameHelper = $this->getContainer()->get('oro_entity.entity_class_name_helper');

        $params = [
            'entityClassName' => $classNameHelper->getUrlSafeClassName(ClassUtils::getClass($shoppingList)),
            'entityId' => $shoppingList->getId()
        ];

        $this->client->request('GET', $this->getUrl('oro_pricing_entity_totals', $params));

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('total', $data);
        $this->assertEquals(303.265, $data['total']['amount']);
        $this->assertEquals('USD', $data['total']['currency']);

        $this->assertArrayHasKey('subtotals', $data);
        $this->assertEquals(303.265, $data['subtotals'][0]['amount']);
        $this->assertEquals('USD', $data['subtotals'][0]['currency']);
    }

    public function testGetEntityTotalsAction()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_pricing_entity_totals'),
            [],
            [],
            $this->generateNoHashNavigationHeader()
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testRecalculateTotalsAction()
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_pricing_recalculate_entity_totals'),
            [],
            [],
            $this->generateNoHashNavigationHeader()
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }
}
