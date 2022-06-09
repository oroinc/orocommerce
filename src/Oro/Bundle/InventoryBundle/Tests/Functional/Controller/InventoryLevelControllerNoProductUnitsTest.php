<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller;

use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\UpdateInventoryLevelsQuantities;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group CommunityEdition
 */
class InventoryLevelControllerNoProductUnitsTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([UpdateInventoryLevelsQuantities::class]);
    }

    public function testNoUnitsOfQuantityReasonMessage()
    {
        /** @var Product $product */
        $product = $this->getReference('product-1');

        //remove product units
        foreach ($product->getUnitPrecisions() as $unit) {
            $product->removeUnitPrecision($unit);
        }
        $this->getContainer()->get('doctrine')->getManagerForClass(Product::class)->flush($product);

        // open product view page
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $product->getId()]));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $inventoryButton = $crawler->filterXPath('//a[contains(., "Manage Inventory")]');
        $this->assertEquals(1, $inventoryButton->count());

        $updateUrl = $inventoryButton->attr('data-url');
        $this->assertNotEmpty($updateUrl);

        // open dialog with levels edit form
        [$route, $parameters] = $this->parseUrl($updateUrl);
        $parameters['_widgetContainer'] = 'dialog';
        $parameters['_wid'] = uniqid('abc', true);

        $crawler = $this->client->request('GET', $this->getUrl($route, $parameters));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $msg = 'Please add at least one Unit of Quantity to the current product to enable inventory management.';
        self::assertStringContainsString($msg, $crawler->html());
    }

    private function parseUrl(string $url): array
    {
        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');
        $parameters = $router->match($url);

        $route = $parameters['_route'];
        unset($parameters['_route'], $parameters['_controller']);

        return [$route, $parameters];
    }
}
