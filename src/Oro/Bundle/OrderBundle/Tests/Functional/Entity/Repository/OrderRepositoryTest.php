<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadConfigurableProductOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrganizations;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Tests\Functional\WebsiteTrait;

class OrderRepositoryTest extends WebTestCase
{
    use WebsiteTrait;

    /** @var OrderRepository */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrganizations::class,
            LoadOrders::class,
            LoadOrderLineItemData::class,
            LoadConfigurableProductOrderLineItemData::class
        ]);

        $this->repository = $this->getRepository();
    }

    public function testHasRecordsWithRemovingCurrencies(): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadOrderUsers::ORDER_USER_1);
        $this->assertNotNull($user);

        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganizations::ORGANIZATION_1);
        $this->assertNotNull($organization);

        $this->assertTrue($this->repository->hasRecordsWithRemovingCurrencies(['USD']));
        $this->assertTrue($this->repository->hasRecordsWithRemovingCurrencies(['EUR']));
        $this->assertFalse($this->repository->hasRecordsWithRemovingCurrencies(['UAH']));
        $this->assertTrue($this->repository->hasRecordsWithRemovingCurrencies(['EUR'], $user->getOrganization()));
        $this->assertFalse($this->repository->hasRecordsWithRemovingCurrencies(['USD'], $organization));
    }

    public function testGetOrderWithRelations(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $orderWithRelations = $this->repository->getOrderWithRelations($order->getId());

        /** @var AbstractLazyCollection $lineItems */
        $lineItems = $orderWithRelations->getLineItems();

        /** @var AbstractLazyCollection $discounts */
        $discounts = $orderWithRelations->getDiscounts();

        $this->assertTrue($lineItems->isInitialized());
        $this->assertTrue($discounts->isInitialized());
    }

    public function testGetLatestOrderedProductsInfo(): void
    {
        $queryBuilder = $this->getRepository()->getLatestOrderedProductsInfo(
            [
                self::getReference(LoadProductData::PRODUCT_1)->getId(),
                self::getReference(LoadProductData::PRODUCT_5)->getId()
            ],
            $this->getDefaultWebsite()->getId(),
            [
                OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
            ]
        );

        $result = $queryBuilder->getQuery()->getArrayResult();

        self::assertCount(2, $result);
        $this->assertContainsRecordWithProductAndCustomerUser(
            $result,
            LoadProductData::PRODUCT_1,
            LoadOrders::ACCOUNT_USER
        );
        $this->assertContainsRecordWithProductAndCustomerUser(
            $result,
            LoadProductData::PRODUCT_5,
            LoadOrders::ACCOUNT_USER
        );
    }

    private function assertContainsRecordWithProductAndCustomerUser(
        array $records,
        string $productReference,
        string $customerUserEmail
    ) {
        $productId = self::getReference($productReference)->getId();
        $customerUserId = $this->getCustomerUserByEmail($customerUserEmail)->getId();
        foreach ($records as $record) {
            // intentional non-strict comparison
            if ($productId == $record['product_id'] && $customerUserId == $record['customer_user_id']) {
                // just increase the asserts counter, as this should be counted as successfully performed assertion
                static::assertTrue(true);
                return;
            }
        }
        static::fail(\sprintf(
            "Failed asserting that there is a record with product %s (product_id=%s)"
             . " and customer user %s (customer_user_id=%s):\n%s",
            $productReference,
            $productId,
            $customerUserEmail,
            $customerUserId,
            \var_export($records, true)
        ));
    }

    public function testGetLatestOrderedProductsInfoWhenConfigurableProductsGiven(): void
    {
        $queryBuilder = $this->getRepository()->getLatestOrderedProductsInfo(
            [
                self::getReference(LoadProductData::PRODUCT_3)->getId(),
            ],
            $this->getDefaultWebsite()->getId(),
            [
                OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
            ]
        );

        self::assertEmpty($queryBuilder->getQuery()->getArrayResult());
    }

    public function testGetLatestOrderedParentProductsInfo(): void
    {
        $queryBuilder = $this->getRepository()->getLatestOrderedParentProductsInfo(
            [
                self::getReference(LoadProductData::PRODUCT_2)->getId(),
                self::getReference(LoadProductData::PRODUCT_7)->getId()
            ],
            $this->getDefaultWebsite()->getId(),
            [
                OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
            ]
        );

        $result = $queryBuilder->getQuery()->getArrayResult();

        $this->assertContainsRecordWithProductAndCustomerUser(
            $result,
            LoadProductData::PRODUCT_2,
            LoadOrders::ACCOUNT_USER
        );
        $this->assertContainsRecordWithProductAndCustomerUser(
            $result,
            LoadProductData::PRODUCT_7,
            LoadOrders::ACCOUNT_USER
        );
    }

    private function getCustomerUserByEmail(string $email): CustomerUser
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(CustomerUser::class)
            ->getRepository(CustomerUser::class)
            ->findOneBy(['email' => $email]);
    }

    /**
     * @return OrderRepository
     */
    private function getRepository()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Order::class)
            ->getRepository(Order::class);
    }

    public function testGetRelatedEntitiesCount()
    {
        $customerUser = $this->getReference(LoadOrders::ACCOUNT_USER);

        self::assertSame(6, $this->repository->getRelatedEntitiesCount($customerUser));
    }

    public function testGetRelatedEntitiesCountZero()
    {
        $customerUserWithoutRelatedEntities = $this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL);

        self::assertSame(0, $this->repository->getRelatedEntitiesCount($customerUserWithoutRelatedEntities));
    }

    public function testResetCustomerUserForSomeEntities()
    {
        $customerUser = $this->getReference(LoadOrders::ACCOUNT_USER);
        $this->getRepository()->resetCustomerUser($customerUser, [
            $this->getReference(LoadOrders::ORDER_1),
            $this->getReference(LoadOrders::ORDER_2),
        ]);

        $orders = $this->getRepository()->findBy(['customerUser' => null]);
        $this->assertCount(2, $orders);
    }

    public function testResetCustomerUser()
    {
        $customerUser = $this->getReference(LoadOrders::ACCOUNT_USER);
        $this->getRepository()->resetCustomerUser($customerUser);

        $orders = $this->getRepository()->findBy(['customerUser' => null]);
        $this->assertCount(6, $orders);
    }
}
