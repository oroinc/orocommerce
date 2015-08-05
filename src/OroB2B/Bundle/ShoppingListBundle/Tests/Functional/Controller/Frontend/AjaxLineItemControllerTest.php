<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class AjaxLineItemControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge(
                $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW),
                ['HTTP_X-CSRF-Header' => 1]
            )
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
            ]
        );
    }

    /**
     * Method testAddProduct
     */
    public function testAddProduct()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');
        /** @var Product $product2 */
        $product = $this->getReference('product.1');

        $crawler = $this->getCrawler($product);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $this->saveForm($crawler, $shoppingList->getId(), 22.2, $unit->getCode(), '');
        $this->assertSaved($form);
    }

    /**
     * Method testAddDuplicate
     *
     * @throws \OroB2B\Bundle\ProductBundle\Exception\InvalidRoundingTypeException
     */
    public function testAddDuplicate()
    {
        /** @var LineItem $existingLineItem */
        $existingLineItem = $this->getReference('shopping_list_line_item.1');

        $crawler = $this->getCrawler($existingLineItem->getProduct());
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $newQuantity = 0.0052;
        $form = $this->saveForm(
            $crawler,
            $existingLineItem->getShoppingList()->getId(),
            $newQuantity,
            $existingLineItem->getUnit()->getCode(),
            ''
        );
        $this->assertSaved($form, $existingLineItem->getId());

        /** @var LineItem $updatedLineItem */
        $updatedLineItem = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BShoppingListBundle:LineItem')
            ->find($existingLineItem->getId());

        $roundingService = $this->getContainer()->get('orob2b_product.service.rounding');
        $expectedNewQuantity = $roundingService->round(
            $existingLineItem->getQuantity() + $newQuantity,
            $existingLineItem->getProduct()->getUnitPrecision($existingLineItem->getUnit()->getCode())->getPrecision()
        );

        $this->assertEquals($updatedLineItem->getQuantity(), $expectedNewQuantity);
    }

    /**
     * Method testCreateNewShoppingList
     */
    public function testCreateNewShoppingList()
    {
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');
        /** @var Product $product2 */
        $product = $this->getReference('product.1');

        $crawler = $this->getCrawler($product);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $this->saveForm($crawler, '', 22.2, $unit->getCode(), 'New Shopping List');
        $this->assertSaved($form);
    }

    /**
     * Method  testEmptyShoppingList
     */
    public function testEmptyShoppingList()
    {
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');
        /** @var Product $product2 */
        $product = $this->getReference('product.1');

        $crawler = $this->getCrawler($product);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $this->saveForm($crawler, '', 22.2, $unit->getCode(), '');

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertEquals(1, $crawler->filter('input.orob2b-shoppinglist-label.error')->count());
    }

    public function testUpdate()
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_shopping_list_line_item_frontend_update_widget',
                [
                    'id' => $lineItem->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form(
            [
                'orob2b_shopping_list_line_item[quantity]' => 33.3,
                'orob2b_shopping_list_line_item[unit]' => $unit->getCode(),
                'orob2b_shopping_list_line_item[notes]' => 'Updated test notes',
            ]
        );

        $this->assertSaved($form);
    }

    public function testAddProductsMassAction()
    {
        $url = $this->getUrl(
            'orob2b_shopping_list_add_products_massaction',
            [
                'gridName' => 'frontend-products-grid',
                'actionName' => 'addproducts',
                'shoppingList' => 'current',
                'inset' => 1,
                'values' => $this->getReference('product.1')->getId()
            ]
        );
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['successful'] === true);
        $this->assertTrue($data['count'] === 1);
    }

    /**
     * @param Product $product
     *
     * @return Crawler
     */
    protected function getCrawler(Product $product)
    {
        return $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_shopping_list_line_item_frontend_add_widget',
                [
                    'productId' => $product->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
    }

    /**
     * @param Crawler     $crawler
     * @param int|null    $shoppingListId
     * @param float       $quantity
     * @param string      $code
     * @param string|null $label
     *
     * @return Form
     */
    protected function saveForm(Crawler $crawler, $shoppingListId, $quantity, $code, $label)
    {
        return $crawler->selectButton('Save')->form(
            [
                'orob2b_shopping_list_frontend_line_item_widget[shoppingList]' => $shoppingListId,
                'orob2b_shopping_list_frontend_line_item_widget[quantity]' => $quantity,
                'orob2b_shopping_list_frontend_line_item_widget[unit]' => $code,
                'orob2b_shopping_list_frontend_line_item_widget[shoppingListLabel]' => $label,
            ]
        );
    }

    /**
     * @param Form $form
     * @param int|null $checkId
     */
    protected function assertSaved(Form $form, $checkId = null)
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $pattern = '/"savedId":\s*(\d+)/i';
        $this->assertRegExp($pattern, $html);

        if ($checkId) {
            preg_match($pattern, $html, $matches);
            $this->assertEquals($matches[1], $checkId);
        }
    }
}
