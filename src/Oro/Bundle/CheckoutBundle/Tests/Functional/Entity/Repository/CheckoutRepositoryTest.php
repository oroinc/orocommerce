<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Entity\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryWithEntityFallbackValuesData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutSubtotals;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as LoadAuthCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutRepositoryTest extends FrontendWebTestCase
{
    private CheckoutRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->setCurrentWebsite('default');
        $this->loadFixtures([
            LoadShoppingListsCheckoutsData::class,
            LoadCustomerUserData::class,
            LoadCategoryProductData::class,
            LoadCategoryWithEntityFallbackValuesData::class,
            LoadCheckoutSubtotals::class
        ]);
        $this->repository = $this->getDoctrine()->getRepository(Checkout::class);
    }

    private function getDoctrine(): ManagerRegistry
    {
        return self::getContainer()->get('doctrine');
    }

    private function getCheckoutIds(iterable $checkouts): array
    {
        $ids = [];
        /** @var Checkout $checkout */
        foreach ($checkouts as $checkout) {
            $ids[] = $checkout->getId();
        }

        return $ids;
    }

    private function deleteAllWorkflowItems(): void
    {
        $em = $this->getDoctrine()->getManagerForClass(WorkflowItem::class);
        $workflowItems = $em->getRepository(WorkflowItem::class)->findAll();
        foreach ($workflowItems as $workflowItem) {
            $em->remove($workflowItem);
        }
        $em->flush();
    }

    public function testGetCheckoutWithRelations(): void
    {
        /** @var Checkout $checkout */
        $expected = $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_1);
        $found = $this->repository->getCheckoutWithRelations($expected->getId());
        self::assertSame($expected, $found);
    }

    public function testCountItemsPerCheckout(): void
    {
        $checkouts = $this->repository->findAll();

        $counts = $this->repository->countItemsPerCheckout($this->getCheckoutIds($checkouts));
        self::assertNotEmpty($counts);

        $found = 0;
        foreach ($checkouts as $checkout) {
            if (isset($counts[$checkout->getId()])) {
                $found++;
            }
        }
        self::assertEquals(count($checkouts), $found);
    }

    public function testGetCheckoutsByIds(): void
    {
        $checkouts = $this->repository->findAll();

        $withSource = 0;
        foreach ($checkouts as $checkout) {
            if (is_object($checkout->getSourceEntity())) {
                $withSource++;
            }
        }

        $checkoutsWithSource = $this->repository->getCheckoutsByIds($this->getCheckoutIds($checkouts));
        $found = 0;
        foreach ($checkouts as $checkout) {
            if (isset($checkoutsWithSource[$checkout->getId()])
                && is_object($checkoutsWithSource[$checkout->getId()])
            ) {
                $found++;
            }
        }
        self::assertEquals($withSource, $found);
    }

    /**
     * @dataProvider findCheckoutWithCurrencyDataProvider
     */
    public function testFindCheckoutByCustomerUserAndSourceCriteriaWithCurrencyByShoppingList(
        string $shoppingList,
        string $currency,
        string $expected
    ): void {
        $foundCheckout = $this->repository->findCheckoutByCustomerUserAndSourceCriteriaWithCurrency(
            $this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL),
            ['shoppingList' => $this->getReference($shoppingList)],
            'b2b_flow_checkout',
            $currency
        );
        self::assertSame($this->getReference($expected), $foundCheckout);
    }

    /**
     * @dataProvider findCheckoutWithCurrencyDataProvider
     */
    public function testFindCheckoutBySourceCriteriaWithCurrencyByShoppingList(
        string $shoppingList,
        string $currency,
        string $expected
    ): void {
        $foundCheckout = $this->repository->findCheckoutBySourceCriteriaWithCurrency(
            ['shoppingList' => $this->getReference($shoppingList)],
            'b2b_flow_checkout',
            $currency
        );
        self::assertSame($this->getReference($expected), $foundCheckout);
    }

    public static function findCheckoutWithCurrencyDataProvider(): array
    {
        return [
            [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_7,
                'currency' => 'USD',
                'expected' => LoadShoppingListsCheckoutsData::CHECKOUT_7
            ],
            [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_8,
                'currency' => 'EUR',
                'expected' => LoadShoppingListsCheckoutsData::CHECKOUT_10
            ]
        ];
    }

    public function testDeleteWithoutWorkflowItem(): void
    {
        $checkouts = $this->repository->findBy(['deleted' => false]);

        $this->deleteAllWorkflowItems();
        $this->repository->deleteWithoutWorkflowItem();

        self::assertCount(count($checkouts), $this->repository->findBy(['deleted' => true]));
    }

    public function testFindByType(): void
    {
        $checkouts = $this->repository->findByPaymentMethod(LoadShoppingListsCheckoutsData::PAYMENT_METHOD);
        self::assertContains($this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_7), $checkouts);
    }

    public function testGetRelatedEntitiesCount(): void
    {
        $customerUser = $this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL);
        self::assertSame(3, $this->repository->getRelatedEntitiesCount($customerUser));
    }

    public function testGetRelatedEntitiesCountZero(): void
    {
        $customerUserWithoutRelatedEntities = $this->getReference(LoadCustomerUserData::LEVEL_1_1_EMAIL);
        self::assertSame(0, $this->repository->getRelatedEntitiesCount($customerUserWithoutRelatedEntities));
    }

    public function testResetCustomerUserForSomeEntities(): void
    {
        $customerUser = $this->getDoctrine()
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadAuthCustomerUserData::AUTH_USER]);

        $this->repository->resetCustomerUser($customerUser, [
            $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_1),
            $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_2),
        ]);

        $checkouts = $this->repository->findBy(['customerUser' => null]);
        self::assertCount(2, $checkouts);
    }

    public function testResetCustomerUser(): void
    {
        $customerUser = $this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL);
        $this->repository->resetCustomerUser($customerUser);

        $checkouts = $this->repository->findBy(['customerUser' => null]);
        self::assertCount(5, $checkouts);
    }

    public function testFindWithInvalidSubtotals(): void
    {
        /** @var CheckoutSubtotal $subtotal1 */
        $subtotal1 = $this->getReference(LoadCheckoutSubtotals::CHECKOUT_SUBTOTAL_1);
        /** @var CheckoutSubtotal $subtotal2 */
        $subtotal2 = $this->getReference(LoadCheckoutSubtotals::CHECKOUT_SUBTOTAL_2);

        $subtotal1->setValid(false);
        $subtotal2->setValid(false);

        $em = $this->getDoctrine()->getManagerForClass(CheckoutSubtotal::class);
        $em->flush();

        $checkouts = $this->repository->findWithInvalidSubtotals();
        self::assertCount(2, $checkouts);

        $ids = $this->getCheckoutIds($checkouts);
        self::assertContains($subtotal1->getCheckout()->getId(), $ids);
        self::assertContains($subtotal2->getCheckout()->getId(), $ids);
    }
}
