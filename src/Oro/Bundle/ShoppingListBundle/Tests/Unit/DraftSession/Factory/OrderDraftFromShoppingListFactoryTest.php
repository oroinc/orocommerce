<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\DraftSession\Factory;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShoppingListBundle\DraftSession\Factory\OrderDraftFromShoppingListFactory;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderDraftFromShoppingListFactoryTest extends TestCase
{
    private WebsiteManager&MockObject $websiteManager;

    private OrderDraftFromShoppingListFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $draftSyncReferenceResolver = $this->createMock(EntityDraftSyncReferenceResolver::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->factory = new OrderDraftFromShoppingListFactory(
            $draftSyncReferenceResolver,
            $this->websiteManager,
        );

        $draftSyncReferenceResolver->expects(self::any())
            ->method('getReference')
            ->willReturnArgument(0);
    }

    public function testSupportsWhenShoppingList(): void
    {
        self::assertTrue($this->factory->supports(ShoppingList::class));
    }

    public function testSupportsWhenNotShoppingList(): void
    {
        self::assertFalse($this->factory->supports(Order::class));
    }

    public function testCreateDraftMapsAllFields(): void
    {
        $draftSessionUuid = 'draft-session-uuid';

        $organization = new Organization();
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $website = new Website();

        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, 42);
        $shoppingList->setOrganization($organization);
        $shoppingList->setCustomer($customer);
        $shoppingList->setCustomerUser($customerUser);
        $shoppingList->setWebsite($website);
        $shoppingList->setCurrency('USD');
        $shoppingList->setNotes('Some notes');

        $this->websiteManager->expects(self::never())
            ->method('getDefaultWebsite');

        $orderDraft = $this->factory->createDraft($shoppingList, $draftSessionUuid);

        self::assertInstanceOf(Order::class, $orderDraft);
        self::assertSame($draftSessionUuid, $orderDraft->getDraftSessionUuid());
        self::assertSame($organization, $orderDraft->getOrganization());
        self::assertSame($customer, $orderDraft->getCustomer());
        self::assertSame($customerUser, $orderDraft->getCustomerUser());
        self::assertSame($website, $orderDraft->getWebsite());
        self::assertSame('USD', $orderDraft->getCurrency());
        self::assertSame('Some notes', $orderDraft->getCustomerNotes());
        self::assertSame(ClassUtils::getClass($shoppingList), $orderDraft->getSourceEntityClass());
        self::assertSame(42, $orderDraft->getSourceEntityId());
        self::assertSame('42', $orderDraft->getSourceEntityIdentifier());
    }

    public function testCreateDraftWhenNoOrganization(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->setWebsite(new Website());

        $this->websiteManager->expects(self::never())
            ->method('getDefaultWebsite');

        $orderDraft = $this->factory->createDraft($shoppingList, 'draft-session-uuid');

        self::assertNull($orderDraft->getOrganization());
    }

    public function testCreateDraftFallsBackToDefaultWebsite(): void
    {
        $defaultWebsite = new Website();

        $shoppingList = new ShoppingList();

        $this->websiteManager->expects(self::once())
            ->method('getDefaultWebsite')
            ->willReturn($defaultWebsite);

        $orderDraft = $this->factory->createDraft($shoppingList, 'draft-session-uuid');

        self::assertSame($defaultWebsite, $orderDraft->getWebsite());
    }

    public function testCreateDraftWhenNoWebsiteAndNoDefaultWebsite(): void
    {
        $shoppingList = new ShoppingList();

        $this->websiteManager->expects(self::once())
            ->method('getDefaultWebsite')
            ->willReturn(null);

        $orderDraft = $this->factory->createDraft($shoppingList, 'draft-session-uuid');

        self::assertNull($orderDraft->getWebsite());
    }
}
