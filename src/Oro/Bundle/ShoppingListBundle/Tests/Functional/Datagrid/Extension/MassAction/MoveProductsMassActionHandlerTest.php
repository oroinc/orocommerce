<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Datagrid\Extension\MassAction;

use Doctrine\ORM\Query;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DTO\SelectedItems;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\MoveProductsMassAction;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\MoveProductsMassActionHandler;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListEmptyConfigurableLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class MoveProductsMassActionHandlerTest extends WebTestCase
{
    private CustomerUser $customerUser;
    private MoveProductsMassActionHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                LoadShoppingListLineItems::class,
                LoadShoppingListEmptyConfigurableLineItems::class,
            ]
        );

        $this->ensureSessionIsAvailable();

        $container = self::getContainer();
        $request = $container->get('request_stack')
            ->getCurrentRequest();
        $request->setMethod(Request::METHOD_POST);
        $request->attributes->set('_theme', 'default');

        $this->customerUser = $this->getCustomerUser();
        $container->get('security.token_storage')
            ->setToken($this->createToken($this->customerUser));

        $this->handler = $container->get('oro_shopping_list.mass_action.move_products_handler');
    }

    public function testHandleWhenRootEntity(): void
    {
        $sourceShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $targetShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2);

        $result = $this->handler->handle(
            new MassActionHandlerArgs(
                $this->getMoveProductsMassAction(),
                $this->getDatagrid($sourceShoppingList),
                new IterableResult($this->getQuery($sourceShoppingList)),
                ['shopping_list_id' => $targetShoppingList->getId()]
            )
        );

        self::assertInstanceOf(MassActionResponse::class, $result);
        self::assertTrue($result->isSuccessful());
        self::assertEquals('One entity has been moved successfully.', $result->getMessage());
        self::assertEquals(['count' => 1], $result->getOptions());
    }

    public function testHandleWhenSavedForLaterMovedToSameShoppingList(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        $lineItem = $this->getReference(LoadShoppingListLineItems::SAVED_FOR_LATER_LINE_ITEM_1);

        $datagrid = $this->getDatagrid(
            $shoppingList,
            'frontend-customer-user-shopping-list-saved-for-later-edit-grid'
        );

        self::assertTrue($shoppingList->getSavedForLaterLineItems()->contains($lineItem));

        $result = $this->handler->handle(
            new MassActionHandlerArgs(
                $this->getMoveProductsMassAction(),
                $datagrid,
                $this->getIterableResultFromDatagrid($datagrid, new SelectedItems([$lineItem->getId()], true)),
                ['values' => $lineItem->getId(), 'shopping_list_id' => $shoppingList->getId()]
            )
        );

        self::assertInstanceOf(MassActionResponse::class, $result);
        self::assertTrue($result->isSuccessful());
        self::assertEquals('One entity has been moved successfully.', $result->getMessage());
        self::assertEquals(['count' => 1], $result->getOptions());
        self::assertFalse($shoppingList->getSavedForLaterLineItems()->contains($lineItem));
        self::assertTrue($shoppingList->getLineItems()->contains($lineItem));

        $doctrine = self::getContainer()->get('doctrine');
        $doctrine->getManagerForClass(LineItem::class)->clear();

        /** @var LineItem $reloadedLineItem */
        $reloadedLineItem = $doctrine->getRepository(LineItem::class)->find($lineItem->getId());
        self::assertNull($reloadedLineItem->getSavedForLaterList());
        self::assertNotNull($reloadedLineItem->getShoppingList());
        self::assertEquals($shoppingList->getId(), $reloadedLineItem->getShoppingList()->getId());
    }

    /**
     * Moving to a different list must also detach the item from the source list's
     * in-memory savedForLaterLineItems collection.
     */
    public function testHandleWhenSavedForLaterMovedToAnotherShoppingList(): void
    {
        /** @var ShoppingList $sourceShoppingList */
        $sourceShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        /** @var ShoppingList $targetShoppingList */
        $targetShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2);
        $lineItem = $this->getReference(LoadShoppingListLineItems::SAVED_FOR_LATER_LINE_ITEM_1);

        $datagrid = $this->getDatagrid(
            $sourceShoppingList,
            'frontend-customer-user-shopping-list-saved-for-later-edit-grid'
        );

        self::assertTrue($sourceShoppingList->getSavedForLaterLineItems()->contains($lineItem));

        $result = $this->handler->handle(
            new MassActionHandlerArgs(
                $this->getMoveProductsMassAction(),
                $datagrid,
                $this->getIterableResultFromDatagrid($datagrid, new SelectedItems([$lineItem->getId()], true)),
                ['values' => $lineItem->getId(), 'shopping_list_id' => $targetShoppingList->getId()]
            )
        );

        self::assertInstanceOf(MassActionResponse::class, $result);
        self::assertTrue($result->isSuccessful());
        self::assertEquals('One entity has been moved successfully.', $result->getMessage());
        self::assertEquals(['count' => 1], $result->getOptions());

        self::assertFalse($sourceShoppingList->getSavedForLaterLineItems()->contains($lineItem));

        $doctrine = self::getContainer()->get('doctrine');
        $doctrine->getManagerForClass(LineItem::class)->clear();

        /** @var LineItem $reloadedLineItem */
        $reloadedLineItem = $doctrine->getRepository(LineItem::class)->find($lineItem->getId());
        self::assertNull($reloadedLineItem->getSavedForLaterList());
        self::assertEquals($targetShoppingList->getId(), $reloadedLineItem->getShoppingList()->getId());
    }

    /**
     * A regular (non-saved-for-later) item moved to its own list must still be skipped.
     */
    public function testHandleWhenNotSavedForLaterAndSameShoppingList(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        $lineItem = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_2);

        $datagrid = $this->getDatagrid($shoppingList);

        $result = $this->handler->handle(
            new MassActionHandlerArgs(
                $this->getMoveProductsMassAction(),
                $datagrid,
                $this->getIterableResultFromDatagrid($datagrid, new SelectedItems([$lineItem->getId()], true)),
                ['values' => $lineItem->getId(), 'shopping_list_id' => $shoppingList->getId()]
            )
        );

        self::assertInstanceOf(MassActionResponse::class, $result);
        self::assertFalse($result->isSuccessful());
        self::assertEquals('No items were moved.', $result->getMessage());
        self::assertEquals(['count' => 0], $result->getOptions());
    }

    /**
     * @dataProvider handleWhenSingleItemDataProvider
     */
    public function testHandleWhenSingleItem(string $lineItemName): void
    {
        /** @var ShoppingList $sourceShoppingList */
        $sourceShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $targetShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2);

        $datagrid = $this->getDatagrid($sourceShoppingList);
        $lineItem = $this->getReference($lineItemName);
        $result = $this->handler->handle(
            new MassActionHandlerArgs(
                $this->getMoveProductsMassAction(),
                $datagrid,
                $this->getIterableResultFromDatagrid($datagrid, new SelectedItems([$lineItem->getId()], true)),
                ['values' => $lineItem->getId(), 'shopping_list_id' => $targetShoppingList->getId()]
            )
        );

        self::assertInstanceOf(MassActionResponse::class, $result);
        self::assertTrue($result->isSuccessful());
        self::assertEquals('One entity has been moved successfully.', $result->getMessage());
        self::assertEquals(['count' => 1], $result->getOptions());

        self::assertEquals($targetShoppingList->getId(), $lineItem->getShoppingList()->getId());
        self::assertFalse($sourceShoppingList->getLineItems()->contains($lineItem));
    }

    public function handleWhenSingleItemDataProvider(): array
    {
        return [
            'simple product' => [
                'lineItemName' => LoadShoppingListLineItems::LINE_ITEM_10,
            ],
            'empty configurable product' => [
                'lineItemName' => LoadShoppingListEmptyConfigurableLineItems::LINE_ITEM_1,
            ],
        ];
    }

    public function testHandleWhenAllItems(): void
    {
        /** @var ShoppingList $sourceShoppingList */
        $sourceShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $targetShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2);

        $datagrid = $this->getDatagrid($sourceShoppingList);
        $result = $this->handler->handle(
            new MassActionHandlerArgs(
                $this->getMoveProductsMassAction(),
                $datagrid,
                $this->getIterableResultFromDatagrid($datagrid, new SelectedItems([], true)),
                ['shopping_list_id' => $targetShoppingList->getId()]
            )
        );

        self::assertInstanceOf(MassActionResponse::class, $result);
        self::assertTrue($result->isSuccessful());
        // product-5 is disabled and auth. checker in class FrontendLineItemsGridVisibilityExtension will filter it out.
        self::assertEquals('4 items have been moved successfully.', $result->getMessage());
        self::assertEquals(['count' => 4], $result->getOptions());

        $lineItem = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_10);

        self::assertEquals($targetShoppingList->getId(), $lineItem->getShoppingList()->getId());
        self::assertFalse($sourceShoppingList->getLineItems()->contains($lineItem));
    }

    private function getCustomerUser(): CustomerUser
    {
        return self::getContainer()
            ->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);
    }

    private function createToken(CustomerUser $customerUser): UsernamePasswordOrganizationToken
    {
        return new UsernamePasswordOrganizationToken(
            $customerUser,
            'k',
            $customerUser->getOrganization(),
            $customerUser->getUserRoles()
        );
    }

    private function getDatagrid(
        ShoppingList $shoppingList,
        string $gridName = 'frontend-customer-user-shopping-list-edit-grid'
    ): DatagridInterface {
        return self::getContainer()
            ->get('oro_datagrid.datagrid.manager')
            ->getDatagrid(
                $gridName,
                [
                    'shopping_list_id' => $shoppingList->getId(),
                ]
            );
    }

    private function getQuery(ShoppingList $shoppingList): Query
    {
        /** @var LineItemRepository $repository */
        $repository = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(LineItem::class)
            ->getrepository(LineItem::class);

        $qb = $repository->createQueryBuilder('li');

        return $qb->where($qb->expr()->eq('li.shoppingList', ':shopping_list_id'))
            ->setParameter('shopping_list_id', $shoppingList->getId())
            ->getQuery();
    }

    private function getIterableResultFromDatagrid(
        DatagridInterface $datagrid,
        SelectedItems $selectedItems
    ): IterableResultInterface {
        return self::getContainer()
            ->get('oro_datagrid.extension.mass_action.iterable_result_factory_registry')
            ->createIterableResult(
                $datagrid->getAcceptedDatasource(),
                $this->getActionConfiguration(),
                $datagrid->getConfig(),
                $selectedItems
            );
    }

    private function getMoveProductsMassAction(): MoveProductsMassAction
    {
        $moveProductsMassAction = new MoveProductsMassAction();
        $moveProductsMassAction->setOptions($this->getActionConfiguration());

        return $moveProductsMassAction;
    }

    private function getActionConfiguration(): ActionConfiguration
    {
        return ActionConfiguration::create(['data_identifier' => 'lineItem.id']);
    }
}
