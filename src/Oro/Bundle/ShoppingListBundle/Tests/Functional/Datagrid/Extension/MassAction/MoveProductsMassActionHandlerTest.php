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
    /** @var CustomerUser */
    private $customerUser;

    /** @var MoveProductsMassActionHandler */
    private $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                LoadShoppingListLineItems::class,
                LoadShoppingListEmptyConfigurableLineItems::class,
            ]
        );

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $container = $this->getContainer();
        $container->get('request_stack')
            ->push($request);

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
                $this->getDatagrid($this->customerUser, $sourceShoppingList),
                new IterableResult($this->getQuery($sourceShoppingList)),
                ['shopping_list_id' => $targetShoppingList->getId()]
            )
        );

        $this->assertInstanceOf(MassActionResponse::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('One entity has been moved successfully.', $result->getMessage());
        $this->assertEquals(['count' => 1], $result->getOptions());

        $lineItem = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_1);

        $this->assertEquals($targetShoppingList->getId(), $lineItem->getShoppingList()->getId());
    }

    /**
     * @dataProvider handleWhenSingleItemDataProvider
     */
    public function testHandleWhenSingleItem(string $lineItemName): void
    {
        /** @var ShoppingList $sourceShoppingList */
        $sourceShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $targetShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2);

        $datagrid = $this->getDatagrid($this->customerUser, $sourceShoppingList);
        $lineItem = $this->getReference($lineItemName);
        $result = $this->handler->handle(
            new MassActionHandlerArgs(
                $this->getMoveProductsMassAction(),
                $datagrid,
                $this->getIterableResultFromDatagrid($datagrid, new SelectedItems([$lineItem->getId()], true)),
                ['values' => $lineItem->getId(), 'shopping_list_id' => $targetShoppingList->getId()]
            )
        );

        $this->assertInstanceOf(MassActionResponse::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('One entity has been moved successfully.', $result->getMessage());
        $this->assertEquals(['count' => 1], $result->getOptions());

        $this->assertEquals($targetShoppingList->getId(), $lineItem->getShoppingList()->getId());
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

        $datagrid = $this->getDatagrid($this->customerUser, $sourceShoppingList);
        $result = $this->handler->handle(
            new MassActionHandlerArgs(
                $this->getMoveProductsMassAction(),
                $datagrid,
                $this->getIterableResultFromDatagrid($datagrid, new SelectedItems([], true)),
                ['shopping_list_id' => $targetShoppingList->getId()]
            )
        );

        $this->assertInstanceOf(MassActionResponse::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('5 items have been moved successfully.', $result->getMessage());
        $this->assertEquals(['count' => 5], $result->getOptions());

        $lineItem = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_10);

        $this->assertEquals($targetShoppingList->getId(), $lineItem->getShoppingList()->getId());
    }

    private function getCustomerUser(): CustomerUser
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);
    }

    private function createToken(CustomerUser $customerUser): UsernamePasswordOrganizationToken
    {
        return new UsernamePasswordOrganizationToken(
            $customerUser,
            false,
            'k',
            $customerUser->getOrganization(),
            $customerUser->getRoles()
        );
    }

    private function getDatagrid(CustomerUser $customerUser, ShoppingList $shoppingList): DatagridInterface
    {
        return $this->getContainer()
            ->get('oro_datagrid.datagrid.manager')
            ->getDatagrid(
                'frontend-customer-user-shopping-list-edit-grid',
                [
                    'shopping_list_id' => $shoppingList->getId(),
                ]
            );
    }

    private function getQuery(ShoppingList $shoppingList): Query
    {
        /** @var LineItemRepository $repository */
        $repository = $this->getContainer()
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
        return $this->getContainer()
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
