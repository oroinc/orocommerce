<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Datagrid;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ShoppingListSubtotalGridListenerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadShoppingListLineItems::class, LoadCombinedProductPrices::class]);
    }

    /**
     * @dataProvider gridDataProvider
     */
    public function testGridRecalculatesInvalidatedSubtotal(string $gridName, array $parameters): void
    {
        $shoppingList = $this->getShoppingList();
        $this->invalidateTotal($shoppingList);

        $expectedAmount = $this->getExpectedAmount($shoppingList);
        self::assertGreaterThan(0.0, $expectedAmount);

        $row = $this->getGridRow($gridName, $parameters, $shoppingList->getId());
        self::assertSame($this->formatCurrency($expectedAmount), $row['subtotal']);
        self::assertTotalRows($shoppingList, $expectedAmount);
    }

    public function gridDataProvider(): array
    {
        return [
            'shopping-list-grid' => ['shopping-list-grid', []],
            'customer-shopping-lists-grid' => [
                'customer-shopping-lists-grid',
                ['customer-shopping-lists-grid[customer_id]' => '{customer_id}'],
            ],
            'customer-user-shopping-lists-grid' => [
                'customer-user-shopping-lists-grid',
                ['customer-user-shopping-lists-grid[customer_user_id]' => '{customer_user_id}'],
            ],
        ];
    }

    public function testGridDoesNotWriteWhenSubtotalIsValid(): void
    {
        $shoppingList = $this->getShoppingList();
        $this->invalidateTotal($shoppingList);

        $this->getGridRow('shopping-list-grid', [], $shoppingList->getId());
        $totalsAfterFirstRequest = $this->fetchTotalRows($shoppingList);

        $this->getGridRow('shopping-list-grid', [], $shoppingList->getId());

        self::assertSame($totalsAfterFirstRequest, $this->fetchTotalRows($shoppingList));
    }

    public function testGridCreatesTotalWhenNoneExists(): void
    {
        $shoppingList = $this->getShoppingList();
        $entityManager = $this->getEntityManager();
        $totals = $entityManager->getRepository(ShoppingListTotal::class)->findBy(['shoppingList' => $shoppingList]);
        foreach ($totals as $total) {
            $entityManager->remove($total);
        }
        $entityManager->flush();

        $expectedAmount = $this->getExpectedAmount($shoppingList);

        $row = $this->getGridRow('shopping-list-grid', [], $shoppingList->getId());
        self::assertSame($this->formatCurrency($expectedAmount), $row['subtotal']);
        self::assertTotalRows($shoppingList, $expectedAmount);
    }

    public function testGridRecalculatesOwnerTotalWhenTotalWithoutCustomerUserExists(): void
    {
        $shoppingList = $this->getShoppingList();
        $this->invalidateTotal($shoppingList);
        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT INTO oro_shopping_list_total'
            . ' (shopping_list_id, customer_user_id, currency, subtotal_value, is_valid)'
            . ' VALUES (:shoppingList, NULL, :currency, 0, false)',
            ['shoppingList' => $shoppingList->getId(), 'currency' => $shoppingList->getCurrency()]
        );

        $expectedAmount = $this->getExpectedAmount($shoppingList);

        $row = $this->getGridRow('shopping-list-grid', [], $shoppingList->getId());
        self::assertSame($this->formatCurrency($expectedAmount), $row['subtotal']);

        $rows = $this->fetchTotalRows($shoppingList);
        self::assertCount(2, $rows);
        foreach ($rows as $totalRow) {
            if (null === $totalRow['customer_user_id']) {
                self::assertFalse((bool)$totalRow['is_valid']);
            } else {
                self::assertEquals($shoppingList->getCustomerUser()->getId(), $totalRow['customer_user_id']);
                self::assertTrue((bool)$totalRow['is_valid']);
                self::assertEqualsWithDelta($expectedAmount, (float)$totalRow['subtotal_value'], 0.0001);
            }
        }

        $response = $this->client->requestGrid(
            ['gridName' => 'shopping-list-grid', 'shopping-list-grid[_pager][_per_page]' => 20],
            [],
            true
        );
        $ids = array_column(self::getJsonResponseContent($response, 200)['data'], 'id');
        self::assertCount(1, array_keys($ids, $shoppingList->getId()));
    }

    private function getShoppingList(): ShoppingList
    {
        return $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
    }

    private function invalidateTotal(ShoppingList $shoppingList): void
    {
        $entityManager = $this->getEntityManager();
        $total = $entityManager->getRepository(ShoppingListTotal::class)
            ->findOneBy(['shoppingList' => $shoppingList, 'currency' => $shoppingList->getCurrency()]);
        if (!$total) {
            $total = new ShoppingListTotal($shoppingList, $shoppingList->getCurrency());
            $entityManager->persist($total);
        }

        $subtotal = new Subtotal();
        $subtotal->setAmount(1000.0);
        $subtotal->setCurrency($shoppingList->getCurrency());
        $total->setSubtotal($subtotal);
        $total->setValid(false);
        $entityManager->flush();
    }

    private function getGridRow(string $gridName, array $parameters, int $shoppingListId): array
    {
        $shoppingList = $this->getShoppingList();
        $replacements = [
            '{customer_id}' => $shoppingList->getCustomer()->getId(),
            '{customer_user_id}' => $shoppingList->getCustomerUser()->getId(),
        ];
        $gridParameters = ['gridName' => $gridName, $gridName . '[_pager][_per_page]' => 20];
        foreach ($parameters as $name => $value) {
            $gridParameters[$name] = strtr((string)$value, $replacements);
        }

        $response = $this->client->requestGrid($gridParameters, [], true);
        $result = self::getJsonResponseContent($response, 200);

        foreach ($result['data'] as $row) {
            if ((int)$row['id'] === $shoppingListId) {
                return $row;
            }
        }

        self::fail(sprintf('Shopping list %d is not present in the "%s" grid.', $shoppingListId, $gridName));
    }

    private function getExpectedAmount(ShoppingList $shoppingList): float
    {
        return self::getContainer()
            ->get('oro_pricing.subtotal_processor.provider.subtotal_line_item_not_priced')
            ->getSubtotalByCurrency($shoppingList, $shoppingList->getCurrency())
            ->getAmount();
    }

    private function formatCurrency(float $amount): string
    {
        return self::getContainer()
            ->get('oro_locale.formatter.number')
            ->formatCurrency($amount, $this->getShoppingList()->getCurrency());
    }

    private function fetchTotalRows(ShoppingList $shoppingList): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT id, customer_user_id, subtotal_value, is_valid FROM oro_shopping_list_total'
            . ' WHERE shopping_list_id = :shoppingList AND currency = :currency ORDER BY id',
            ['shoppingList' => $shoppingList->getId(), 'currency' => $shoppingList->getCurrency()]
        );
    }

    private function assertTotalRows(ShoppingList $shoppingList, float $expectedAmount): void
    {
        $rows = $this->fetchTotalRows($shoppingList);
        self::assertCount(1, $rows);
        self::assertTrue((bool)$rows[0]['is_valid']);
        self::assertEqualsWithDelta($expectedAmount, (float)$rows[0]['subtotal_value'], 0.0001);
        self::assertEquals($shoppingList->getCustomerUser()->getId(), $rows[0]['customer_user_id']);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(ShoppingListTotal::class);
    }
}
