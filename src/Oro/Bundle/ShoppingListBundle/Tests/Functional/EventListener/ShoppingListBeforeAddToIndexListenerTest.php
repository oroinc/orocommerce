<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerVisitors;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\WebsiteManagerTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByIdTopic;
use Oro\Bundle\SearchBundle\Transformer\MessageTransformerInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @method ContainerInterface getContainer()
 */
class ShoppingListBeforeAddToIndexListenerTest extends FrontendWebTestCase
{
    use MessageQueueExtension;
    use WebsiteManagerTrait;

    protected ?ShoppingListManager $shoppingListManager;

    protected ?GuestShoppingListManager $guestShoppingListMgr;

    protected ?MessageTransformerInterface $messageTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            LoadOrganization::class,
            LoadCustomer::class,
            LoadCustomerVisitors::class,
            LoadCustomerUser::class
        ]);
        $this->setCurrentWebsite();
        $this->enableVisitor();

        $this->shoppingListManager = $this->getContainer()->get('oro_shopping_list.manager.shopping_list');
        $this->guestShoppingListMgr = $this->getContainer()->get('oro_shopping_list.manager.guest_shopping_list');
        $this->messageTransformer = $this->getContainer()->get('oro_search.transformer.message');
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

    /**
     * @param ShoppingList $shoppingList
     * @return array
     */
    protected function getExpectedMessage(ShoppingList $shoppingList): array
    {
        $entities = [$shoppingList];
        $message = $this->messageTransformer->transform($entities);
        return !empty($message) ? reset($message) : [];
    }

    protected function enableVisitor(): void
    {
        $visitor = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR);

        $this->getContainer()->get('security.token_storage')->setToken(
            new AnonymousCustomerUserToken('', [], $visitor)
        );
    }

    /**
     * @param ShoppingList $shoppingList
     * @return ShoppingList
     */
    protected function editShoppingList(ShoppingList $shoppingList): ShoppingList
    {
        $shoppingList = $this->shoppingListManager->edit($shoppingList, 'Shopping List Edited');
        $doctrine = $this->getContainer()->get('doctrine');
        $entityManager = $doctrine->getManagerForClass(ShoppingList::class);
        $entityManager->persist($shoppingList);
        $entityManager->flush();

        return $shoppingList;
    }

    /**
     * @param CustomerUser|null $customerUser
     */
    protected function setCustomerUserToTokenStorage(?CustomerUser $customerUser): void
    {
        $token = new UsernamePasswordToken($customerUser, 'user', 'key');
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
    }
}
