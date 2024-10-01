<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResetCustomerUserTrait;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResettableCustomerUserRepositoryInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadConfigurableProductOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrganizations;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Tests\Functional\WebsiteTrait;

/**
 * @dbIsolationPerTest
 */
class LatestOrderedProductsTest extends WebTestCase implements ResettableCustomerUserRepositoryInterface
{
    use ResetCustomerUserTrait;
    use WebsiteTrait;

    private OrderRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrganizations::class,
            LoadOrders::class,
            LoadOrderLineItemData::class,
            LoadConfigurableProductOrderLineItemData::class,
        ]);

        $this->repository = $this->getRepository();
    }

    public function testGetLatestOrderedProductsInfoWithResetCustomerUser()
    {
        $customerUser = $this->getReference(LoadOrders::ACCOUNT_USER);
        $this->getRepository()->resetCustomerUser($customerUser, [
            $this->getReference(LoadOrders::ORDER_2),
            $this->getReference(LoadOrders::ORDER_3),
        ]);

        $queryBuilder = $this->getRepository()->getLatestOrderedProductsInfo(
            [
                $this->getReference(LoadProductData::PRODUCT_1)->getId(),
                $this->getReference(LoadProductData::PRODUCT_5)->getId()
            ],
            $this->getDefaultWebsite()->getId(),
            [
                ExtendHelper::buildEnumOptionId(
                    Order::INTERNAL_STATUS_CODE,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
                )
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

        $this->getRepository()->resetCustomerUser($customerUser);
        $queryBuilder = $this->getRepository()->getLatestOrderedProductsInfo(
            [
                $this->getReference(LoadProductData::PRODUCT_1)->getId(),
                $this->getReference(LoadProductData::PRODUCT_5)->getId()
            ],
            $this->getDefaultWebsite()->getId(),
            [
                ExtendHelper::buildEnumOptionId(
                    Order::INTERNAL_STATUS_CODE,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
                )
            ]
        );

        $result = $queryBuilder->getQuery()->getArrayResult();
        self::assertEmpty($result);
    }

    public function testGetLatestOrderedParentProductsInfoWithResetCustomerUser()
    {
        $customerUser = $this->getReference(LoadOrders::ACCOUNT_USER);
        $this->getRepository()->resetCustomerUser($customerUser, [
            $this->getReference(LoadOrders::ORDER_2),
            $this->getReference(LoadOrders::ORDER_4),
        ]);

        $queryBuilder = $this->getRepository()->getLatestOrderedParentProductsInfo(
            [
                $this->getReference(LoadProductData::PRODUCT_2)->getId(),
                $this->getReference(LoadProductData::PRODUCT_7)->getId()
            ],
            $this->getDefaultWebsite()->getId(),
            [
                ExtendHelper::buildEnumOptionId(
                    Order::INTERNAL_STATUS_CODE,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
                )
            ]
        );

        $result = $queryBuilder->getQuery()->getArrayResult();
        self::assertCount(1, $result);
        $this->assertContainsRecordWithProductAndCustomerUser(
            $result,
            LoadProductData::PRODUCT_7,
            LoadOrders::ACCOUNT_USER
        );

        $this->getRepository()->resetCustomerUser($customerUser);

        $queryBuilder = $this->getRepository()->getLatestOrderedParentProductsInfo(
            [
                $this->getReference(LoadProductData::PRODUCT_1)->getId(),
                $this->getReference(LoadProductData::PRODUCT_5)->getId()
            ],
            $this->getDefaultWebsite()->getId(),
            [
                OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
            ]
        );

        $result = $queryBuilder->getQuery()->getArrayResult();
        self::assertEmpty($result);
    }

    private function getRepository(): OrderRepository
    {
        return self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Order::class)
            ->getRepository(Order::class);
    }

    private function getCustomerUserByEmail(string $email): CustomerUser
    {
        return self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(CustomerUser::class)
            ->getRepository(CustomerUser::class)
            ->findOneBy(['email' => $email]);
    }

    private function assertContainsRecordWithProductAndCustomerUser(
        array $records,
        string $productReference,
        string $customerUserEmail
    ): void {
        $productId = $this->getReference($productReference)->getId();
        $customerUserId = $this->getCustomerUserByEmail($customerUserEmail)->getId();
        foreach ($records as $record) {
            // intentional non-strict comparison
            if ($productId == $record['product_id'] && $customerUserId == $record['customer_user_id']) {
                // just increase the asserts counter, as this should be counted as successfully performed assertion
                static::assertTrue(true);
                return;
            }
        }
        static::fail(sprintf(
            'Failed asserting that there is a record with product %s (product_id=%s)'
            . " and customer user %s (customer_user_id=%s):\n%s",
            $productReference,
            $productId,
            $customerUserEmail,
            $customerUserId,
            \var_export($records, true)
        ));
    }
}
