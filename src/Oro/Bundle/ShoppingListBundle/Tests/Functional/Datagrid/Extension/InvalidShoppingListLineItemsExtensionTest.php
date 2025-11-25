<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Datagrid\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class InvalidShoppingListLineItemsExtensionTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private InvalidShoppingListLineItemsProvider $provider;
    private CustomerUser $customerUser;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadShoppingLists::class, LoadShoppingListLineItems::class]);

        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->provider = self::getContainer()->get('oro_shopping_list.provider.invalid_shopping_list_line_items');

        $this->ensureSessionIsAvailable();

        $container = self::getContainer();
        $request = $container->get('request_stack')
            ->getCurrentRequest();
        $request->setMethod(Request::METHOD_POST);
        $request->attributes->set('_theme', 'default');

        $this->customerUser = $this->getCustomerUser();
        $container->get('security.token_storage')
            ->setToken($this->createToken($this->customerUser));
    }

    public function testGrid(): void
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $lineItems = $shoppingList->getLineItems();

        $firstLineItem = $lineItems->first();
        if ($firstLineItem) {
            $firstLineItem->setQuantity(10000000);
            $this->entityManager->persist($firstLineItem);
            $this->entityManager->flush();
        }

        $datagrid = self::getContainer()
            ->get('oro_datagrid.datagrid.manager')
            ->getDatagrid(
                'frontend-customer-user-shopping-list-invalid-line-items-grid',
                [
                    'shopping_list_id' => $shoppingList->getId(),
                    'triggered_by' => 'checkout',
                ]
            );

        $invalidItemIds = $this->provider->getInvalidLineItemsIds($lineItems, 'checkout');

        $data = $datagrid->getData();
        $totalRecords = $data->getTotalRecords();

        self::assertEquals(count($invalidItemIds), $totalRecords);

        $records = $data->getData();
        self::assertCount(count($invalidItemIds), $records);
        self::assertEquals($invalidItemIds, $datagrid->getMetadata()->offsetGet('invalidIds'));
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
}
