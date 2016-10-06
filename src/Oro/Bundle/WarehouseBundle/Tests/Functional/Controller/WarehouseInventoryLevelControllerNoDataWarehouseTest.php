<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Functional\Controller;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class WarehouseInventoryLevelControllerNoDataWarehouseTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions'
            ]
        );
    }

    public function testNoWarehouseReasonMessage()
    {
        /** @var Product $product */
        $product = $this->getReference('product.1');

        // open product view page
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $product->getId()]));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $inventoryButton = $crawler->filterXPath('//a[@title="Inventory"]');
        $this->assertEquals(1, $inventoryButton->count());

        $updateUrl = $inventoryButton->attr('data-url');
        $this->assertNotEmpty($updateUrl);

        // open dialog with levels edit form
        list($route, $parameters) = $this->parseUrl($updateUrl);
        $parameters['_widgetContainer'] = 'dialog';
        $parameters['_wid'] = uniqid('abc', true);

        $crawler = $this->client->request('GET', $this->getUrl($route, $parameters));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $this->assertContains('There are no warehouses configured in the system.', $crawler->html());
    }

    /**
     * @param string $url
     * @return array
     */
    protected function parseUrl($url)
    {
        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');
        $parameters = $router->match($url);

        $route = $parameters['_route'];
        unset($parameters['_route'], $parameters['_controller']);

        return [$route, $parameters];
    }
}
