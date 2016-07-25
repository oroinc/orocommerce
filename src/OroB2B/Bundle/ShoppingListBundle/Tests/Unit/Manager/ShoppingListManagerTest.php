<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Rounding\QuantityRoundingService;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ShoppingListManagerTest extends \PHPUnit_Framework_TestCase
{
    const CURRENCY_EUR = 'EUR';
    use EntityTrait;

    /** @var ShoppingList */
    protected $shoppingListOne;

    /** @var ShoppingList */
    protected $shoppingListTwo;

    /** @var ShoppingListManager */
    protected $manager;

    /** @var ShoppingList[] */
    protected $shoppingLists = [];

    /** @var LineItem[] */
    protected $lineItems = [];

    /** @var ManagerRegistry */
    protected $registry = [];

    protected function setUp()
    {
        $this->shoppingListOne = $this->getShoppingList(1, true);
        $this->shoppingListTwo = $this->getShoppingList(2, false);

        $tokenStorage = $this->getTokenStorage(
            (new AccountUser())
                ->setFirstName('skip')
                ->setAccount(new Account())
                ->setOrganization(new Organization())
        );

        $this->registry = $this->getManagerRegistry();

        $this->manager = new ShoppingListManager(
            $this->registry,
            $tokenStorage,
            $this->getTranslator(),
            $this->getRoundingService(),
            $this->getUserCurrencyManager(),
            $this->getWebsiteManager(),
            $this->getShoppingListTotalManager()
        );
    }

    public function testCreate()
    {
        $shoppingList = $this->manager->create();

        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
        $this->assertInstanceOf('OroB2B\Bundle\AccountBundle\Entity\Account', $shoppingList->getAccount());
        $this->assertInstanceOf('OroB2B\Bundle\AccountBundle\Entity\AccountUser', $shoppingList->getAccountUser());
        $this->assertInstanceOf('Oro\Bundle\OrganizationBundle\Entity\Organization', $shoppingList->getOrganization());
    }

    public function testCreateCurrent()
    {
        $this->manager->setCurrent(
            (new AccountUser())->setFirstName('setCurrent'),
            $this->shoppingListTwo
        );
        $this->assertTrue($this->shoppingListTwo->isCurrent());
        $this->assertFalse($this->shoppingListOne->isCurrent());
    }

    public function testSetCurrent()
    {
        $this->assertEmpty($this->shoppingLists);
        $this->manager->createCurrent();
        $this->assertCount(1, $this->shoppingLists);
        /** @var ShoppingList $list */
        $list = array_shift($this->shoppingLists);
        $this->assertTrue($list->isCurrent());
    }

    public function testAddLineItem()
    {
        $shoppingList = new ShoppingList();
        $lineItem = new LineItem();
        $this->manager->addLineItem($lineItem, $shoppingList);
        $this->assertEquals(1, $shoppingList->getLineItems()->count());
    }

    public function testAddLineItemDuplicate()
    {
        $shoppingList = new ShoppingList();
        $reflectionClass = new \ReflectionClass(get_class($shoppingList));
        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($shoppingList, 1);

        $lineItem = (new LineItem())
            ->setUnit(
                (new ProductUnit())
                    ->setCode('test')
                    ->setDefaultPrecision(1)
            )
            ->setQuantity(10);

        $this->manager->addLineItem($lineItem, $shoppingList);
        $this->assertEquals(1, $shoppingList->getLineItems()->count());
        $this->assertEquals(1, count($this->lineItems));
        $lineItemDuplicate = clone $lineItem;
        $lineItemDuplicate->setQuantity(5);
        $this->manager->addLineItem($lineItemDuplicate, $shoppingList);
        $this->assertEquals(1, $shoppingList->getLineItems()->count());
        /** @var LineItem $resultingItem */
        $resultingItem = array_shift($this->lineItems);
        $this->assertEquals(15, $resultingItem->getQuantity());
    }

    public function testAddLineItemDuplicateAndConcatNotes()
    {
        $shoppingList = new ShoppingList();
        $reflectionClass = new \ReflectionClass(get_class($shoppingList));
        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($shoppingList, 1);

        $lineItem = (new LineItem())
            ->setUnit(
                (new ProductUnit())
                    ->setCode('test')
                    ->setDefaultPrecision(1)
            )
            ->setNotes('Notes');

        $this->manager->addLineItem($lineItem, $shoppingList);

        $lineItemDuplicate = clone $lineItem;
        $lineItemDuplicate->setNotes('Duplicated Notes');

        $this->manager->addLineItem($lineItemDuplicate, $shoppingList, true, true);

        $this->assertEquals(1, $shoppingList->getLineItems()->count());

        /** @var LineItem $resultingItem */
        $resultingItem = array_shift($this->lineItems);
        $this->assertSame('Notes Duplicated Notes', $resultingItem->getNotes());
    }

    /**
     * @dataProvider removeProductDataProvider
     *
     * @param array $lineItems
     * @param array $relatedLineItems
     * @param bool $flush
     * @param bool $expectedFlush
     */
    public function testRemoveProduct(array $lineItems, array $relatedLineItems, $flush, $expectedFlush)
    {
        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => 42]);

        foreach ($lineItems as $lineItem) {
            $this->shoppingListOne->addLineItem($lineItem);
        }

        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->registry->getManagerForClass('OroB2BShoppingListBundle:LineItem');
        $manager->expects($this->exactly(count($relatedLineItems)))
            ->method('remove')
            ->willReturnCallback(
                function (LineItem $item) {
                    $this->lineItems[] = $item;
                }
            );
        $manager->expects($expectedFlush ? $this->once() : $this->never())->method('flush');

        /** @var LineItemRepository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $manager->getRepository('OroB2BShoppingListBundle:LineItem');
        $repository->expects($this->once())
            ->method('getItemsByShoppingListAndProduct')
            ->with($this->shoppingListOne, $product)
            ->willReturn($relatedLineItems);

        $result = $this->manager->removeProduct($this->shoppingListOne, $product, $flush);

        $this->assertEquals(count($relatedLineItems), $result);

        foreach ($relatedLineItems as $lineItem) {
            $this->assertContains($lineItem, $this->lineItems);
            $this->assertNotContains($lineItem, $this->shoppingListOne->getLineItems());
        }

        $this->assertEquals(
            count($lineItems) - count($relatedLineItems),
            $this->shoppingListOne->getLineItems()->count()
        );
    }

    /**
     * @return array
     */
    public function removeProductDataProvider()
    {
        /** @var LineItem $lineItem1 */
        $lineItem1 = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem', ['id' => 35]);

        /** @var LineItem $lineItem2 */
        $lineItem2 = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem', ['id' => 36]);

        /** @var LineItem $lineItem3 */
        $lineItem3 = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem', ['id' => 37]);

        return [
            [
                'lineItems' => [$lineItem1, $lineItem2, $lineItem3],
                'relatedLineItems' => [$lineItem1, $lineItem3],
                'flush' => true,
                'expectedFlush' => true
            ],
            [
                'lineItems' => [$lineItem1, $lineItem2, $lineItem3],
                'relatedLineItems' => [],
                'flush' => true,
                'expectedFlush' => false
            ],
            [
                'lineItems' => [$lineItem1, $lineItem2, $lineItem3],
                'relatedLineItems' => [$lineItem2],
                'flush' => false,
                'expectedFlush' => false
            ]
        ];
    }

    public function testGetForCurrentUser()
    {
        $shoppingList = $this->manager->getForCurrentUser();
        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
    }

    public function testBulkAddLineItems()
    {
        $shoppingList = new ShoppingList();
        $lineItems = [];
        for ($i = 0; $i < 10; $i++) {
            $lineItems[] = new LineItem();
        }

        $this->manager->bulkAddLineItems($lineItems, $shoppingList, 10);
        $this->assertEquals(10, $shoppingList->getLineItems()->count());
    }

    public function testGetShoppingLists()
    {
        $user = new AccountUser();

        $shoppingList1 = $this->getShoppingList(10, false);
        $shoppingList2 = $this->getShoppingList(20, false);
        $shoppingList3 = $this->getShoppingList(30, true);

        /* @var $repository ShoppingListRepository|\PHPUnit_Framework_MockObject_MockObject */
        $repository = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn([$shoppingList3, $shoppingList1, $shoppingList2]);

        /* @var $entityManager EntityManager|\PHPUnit_Framework_MockObject_MockObject */
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        /* @var $registry ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $manager = new ShoppingListManager(
            $registry,
            $this->getTokenStorage($user),
            $this->getTranslator(),
            $this->getRoundingService(),
            $this->getUserCurrencyManager(),
            $this->getWebsiteManager(),
            $this->getShoppingListTotalManager()
        );

        $this->assertEquals(
            [$shoppingList3, $shoppingList1, $shoppingList2],
            $manager->getShoppingLists()
        );
    }

    /**
     * @param AccountUser $accountUser
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected function getTokenStorage(AccountUser $accountUser)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $securityToken */
        $securityToken = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $securityToken->expects($this->any())
            ->method('getUser')
            ->willReturn($accountUser);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface $tokenStorage */
        $tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($securityToken);

        return $tokenStorage;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->getMock('Symfony\Component\Translation\TranslatorInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QuantityRoundingService
     */
    protected function getRoundingService()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|QuantityRoundingService $roundingService */
        $roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\QuantityRoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $roundingService->expects($this->any())
            ->method('roundQuantity')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return round($value, 0, PHP_ROUND_HALF_UP);
                    }
                )
            );

        return $roundingService;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected function getManagerRegistry()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $shoppingListRepository->expects($this->any())
            ->method('findCurrentForAccountUser')
            ->willReturnCallback(function (AccountUser $accountUser) {
                if ($accountUser->getFirstName() === 'setCurrent') {
                    return $this->shoppingListOne;
                }

                return null;
            });

        /** @var \PHPUnit_Framework_MockObject_MockObject|LineItemRepository $lineItemRepository */
        $lineItemRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $lineItemRepository
            ->expects($this->any())
            ->method('findDuplicate')
            ->willReturnCallback(function (LineItem $lineItem) {
                /** @var ArrayCollection $shoppingListLineItems */
                $shoppingListLineItems = $lineItem->getShoppingList()->getLineItems();
                if ($lineItem->getShoppingList()->getId() === 1
                    && $shoppingListLineItems->count() > 0
                    && $shoppingListLineItems->current()->getUnit() === $lineItem->getUnit()
                ) {
                    return $lineItem->getShoppingList()->getLineItems()->current();
                }

                return null;
            });

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager $entityManager */
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValueMap([
                ['OroB2BShoppingListBundle:ShoppingList', $shoppingListRepository],
                ['OroB2BShoppingListBundle:LineItem', $lineItemRepository]
            ]));

        $entityManager->expects($this->any())
            ->method('persist')
            ->willReturnCallback(function ($obj) {
                if ($obj instanceof ShoppingList) {
                    $this->shoppingLists[] = $obj;
                }
                if ($obj instanceof LineItem) {
                    $this->lineItems[] = $obj;
                }
            });

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        return $managerRegistry;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ShoppingListTotalManager
     */
    protected function getShoppingListTotalManager()
    {
        return $this->getMockBuilder(ShoppingListTotalManager::class)
                ->disableOriginalConstructor()
                ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|UserCurrencyManager
     */
    protected function getUserCurrencyManager()
    {
        $userCurrencyManager = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager')
            ->disableOriginalConstructor()
            ->getMock();

        $userCurrencyManager->expects($this->any())
            ->method('getUserCurrency')
            ->willReturn(self::CURRENCY_EUR);

        return $userCurrencyManager;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|WebsiteManager
     */
    protected function getWebsiteManager()
    {
        $websiteManager = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager')
            ->disableOriginalConstructor()
            ->getMock();
        $website = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Entity\Website')
            ->disableOriginalConstructor()
            ->getMock();

        $websiteManager->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        return $websiteManager;
    }

    /**
     * @param int  $id
     * @param bool $isCurrent
     *
     * @return ShoppingList
     */
    protected function getShoppingList($id, $isCurrent)
    {
        return $this->getEntity(
            'OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList',
            ['id' => $id, 'current' => $isCurrent]
        );
    }
}
