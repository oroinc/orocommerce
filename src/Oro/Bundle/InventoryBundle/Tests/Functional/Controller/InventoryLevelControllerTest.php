<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\UpdateInventoryLevelsQuantities;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group CommunityEdition
 */
class InventoryLevelControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([UpdateInventoryLevelsQuantities::class]);
    }

    public function testIndexAction()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_inventory_level_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('inventory-grid', $crawler->html());
    }

    public function testUpdateAction()
    {
        /** @var Product $product */
        $product = $this->getReference('product-1');

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

        // check levels grid
        $levelsGrid = $crawler->filterXPath('//div[starts-with(@id,"grid-inventory-level-grid")]');
        $this->assertEquals(1, $levelsGrid->count());

        $gridConfig = self::jsonToArray($levelsGrid->attr('data-page-component-options'));
        $gridData = $gridConfig['data']['data'];
        $this->assertLevelsGridData($product, $gridData);

        // change quantities and submit form
        $changeSet = [];
        $gridQuantities = $this->getGridQuantities($gridData);
        foreach ($gridQuantities as $combinedId => $quantity) {
            if ($quantity) {
                $changeSet[$combinedId]['levelQuantity'] = null;
            } else {
                $changeSet[$combinedId]['levelQuantity'] = mt_rand(1, 100);
            }
        }

        $form = $crawler->selectButton('Save')->form();
        $form['oro_inventory_level_grid'] = json_encode($changeSet, JSON_THROW_ON_ERROR);
        $this->client->submit($form);
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        // assert data saved successfully
        $this->assertLevelsData($changeSet);
    }

    private function assertLevelsData(array $data): void
    {
        $repository = $this->getRepository(InventoryLevel::class);

        foreach ($data as $combinedId => $row) {
            [$precisionId] = explode('_', $combinedId, 2);
            $quantity = $row['levelQuantity'];

            /** @var InventoryLevel|null $level */
            $level = $repository->createQueryBuilder('level')
                ->andWhere('IDENTITY(level.productUnitPrecision) = :precisionId')
                ->setParameter('precisionId', $precisionId)
                ->getQuery()
                ->getOneOrNullResult();

            if ($quantity) {
                $this->assertNotNull($level);
                $this->assertEquals($quantity, $level->getQuantity());
            } else {
                $this->assertNull($level);
            }
        }
    }

    private function getGridQuantities(array $data): array
    {
        $quantities = [];
        foreach ($data as $row) {
            $this->assertArrayHasKey('levelQuantity', $row);
            $this->assertArrayHasKey('combinedId', $row);
            $quantities[$row['combinedId']] = $row['levelQuantity'];
        }
        return $quantities;
    }

    private function assertLevelsGridData(Product $product, array $data): void
    {
        $expectedCombinedIds = [];
        foreach ($product->getUnitPrecisions() as $precision) {
            $expectedCombinedIds[] = sprintf('%s', $precision->getId());
        }

        $this->assertSameSize($expectedCombinedIds, $data);
        foreach ($data as $row) {
            $this->assertArrayHasKey('combinedId', $row);
            $this->assertContains($row['combinedId'], $expectedCombinedIds);
        }
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

    private function getRepository(string $class): EntityRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository($class);
    }
}
