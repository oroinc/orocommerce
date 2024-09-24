<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerVisitors;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByIdTopic;
use Oro\Bundle\SearchBundle\Transformer\MessageTransformerInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ShoppingListBeforeAddToIndexListenerTest extends FrontendWebTestCase
{
    use MessageQueueExtension;

    private ShoppingListManager $shoppingListManager;
    private GuestShoppingListManager $guestShoppingListMgr;
    private MessageTransformerInterface $messageTransformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrganization::class,
            LoadCustomer::class,
            LoadCustomerVisitors::class,
            LoadCustomerUser::class
        ]);
        $this->setCurrentWebsite();
        $this->enableVisitor();

        $this->shoppingListManager = self::getContainer()->get('oro_shopping_list.manager.shopping_list');
        $this->guestShoppingListMgr = self::getContainer()->get('oro_shopping_list.manager.guest_shopping_list');
        $this->messageTransformer = self::getContainer()->get('oro_search.transformer.message');
    }

    public function testGuestShoppingListIndex(): void
    {
        $expectedMessages = [];

        $shoppingList = $this->guestShoppingListMgr->createShoppingListForCustomerVisitor();
        $expectedMessages[] = $this->getExpectedMessage($shoppingList);

        $shoppingList = $this->editShoppingList($shoppingList); // anonymous shopping list can't be edited.
        $expectedMessages[] = $this->getExpectedMessage($shoppingList);

        $messages = self::getSentMessagesByTopic(IndexEntitiesByIdTopic::getName());

        $this->assertNotEquals($expectedMessages, $messages);
    }

    public function testCustomerUserShoppingListIndex(): void
    {
        $expectedMessages = [];
        $customerUser = $this->getReference(LoadCustomerUser::CUSTOMER_USER);
        $this->setCustomerUserToTokenStorage($customerUser);

        $shoppingList = $this->shoppingListManager->create(true, '', $customerUser);
        $expectedMessages[] = $this->getExpectedMessage($shoppingList);

        $shoppingList = $this->editShoppingList($shoppingList);
        $expectedMessages[] = $this->getExpectedMessage($shoppingList);

        self::assertMessagesSent(IndexEntitiesByIdTopic::getName(), $expectedMessages);
    }

    private function getExpectedMessage(ShoppingList $shoppingList): array
    {
        $entities = [$shoppingList];
        $message = $this->messageTransformer->transform($entities);
        return !empty($message) ? reset($message) : [];
    }

    private function enableVisitor(): void
    {
        $visitor = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR);

        self::getContainer()->get('security.token_storage')->setToken(
            new AnonymousCustomerUserToken($visitor)
        );
    }

    private function editShoppingList(ShoppingList $shoppingList): ShoppingList
    {
        $shoppingList = $this->shoppingListManager->edit($shoppingList, 'Shopping List Edited');
        $doctrine = self::getContainer()->get('doctrine');
        $entityManager = $doctrine->getManagerForClass(ShoppingList::class);
        $entityManager->persist($shoppingList);
        $entityManager->flush();

        return $shoppingList;
    }

    private function setCustomerUserToTokenStorage(?CustomerUser $customerUser): void
    {
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($customerUser, 'key')
        );
    }
}
