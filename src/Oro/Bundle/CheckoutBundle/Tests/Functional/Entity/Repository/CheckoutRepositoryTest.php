<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Entity\Repository;

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

        $this->repository = $this->getRepository();
    }

    protected function getRepository(): CheckoutRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(Checkout::class);
    }

    public function testGetCheckoutWithRelations()
    {
        $repository = $this->getRepository();

        $expected = $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_1);
        $result = $repository->getCheckoutWithRelations($expected->getId());

        $this->assertSame($expected, $result);
    }

    public function testCountItemsPerCheckout()
    {
        $repository = $this->getRepository();

        $checkouts = $repository->findAll();

        $ids = [];

        foreach ($checkouts as $checkout) {
            $ids[] = $checkout->getId();
        }

        $counts = $repository->countItemsPerCheckout($ids);

        $this->assertTrue(count($counts) > 0);

        $found = 0;

        foreach ($checkouts as $checkout) {
            if (isset($counts[$checkout->getId()])) {
                $found++;
            }
        }

        $this->assertEquals(count($ids), $found);
    }

    public function testGetCheckoutsByIds()
    {
        $repository = $this->getRepository();

        $checkouts = $repository->findAll();

        $ids = [];

        $withSource = 0;

        foreach ($checkouts as $checkout) {
            $ids[] = $checkout->getId();

            if (is_object($checkout->getSourceEntity())) {
                $withSource++;
            }
        }

        $sources = $repository->getCheckoutsByIds($ids);

        $found = 0;

        foreach ($checkouts as $checkout) {
            if (isset($sources[$checkout->getId()]) && is_object($sources[$checkout->getId()])) {
                $found++;
            }
        }

        $this->assertEquals($withSource, $found);
    }

    /**
     * @dataProvider findCheckoutWithCurrencyDataProvider
     */
    public function testFindCheckoutByCustomerUserAndSourceCriteriaWithCurrencyByShoppingList(
        string $shoppingList,
        string $currency,
        string $expected
    ): void {
        $this->assertSame(
            $this->getReference($expected),
            $this->getRepository()->findCheckoutByCustomerUserAndSourceCriteriaWithCurrency(
                $this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL),
                ['shoppingList' => $this->getReference($shoppingList)],
                'b2b_flow_checkout',
                $currency
            )
        );
    }

    /**
     * @dataProvider findCheckoutWithCurrencyDataProvider
     */
    public function testFindCheckoutBySourceCriteriaWithCurrencyByShoppingList(
        string $shoppingList,
        string $currency,
        string $expected
    ): void {
        $this->assertSame(
            $this->getReference($expected),
            $this->getRepository()->findCheckoutBySourceCriteriaWithCurrency(
                ['shoppingList' => $this->getReference($shoppingList)],
                'b2b_flow_checkout',
                $currency
            )
        );
    }

    public function findCheckoutWithCurrencyDataProvider(): array
    {
        return [
            [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_7,
                'currency' => 'USD',
                'expected' => LoadShoppingListsCheckoutsData::CHECKOUT_7,
            ],
            [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_8,
                'currency' => 'EUR',
                'expected' => LoadShoppingListsCheckoutsData::CHECKOUT_10,
            ],
        ];
    }

    public function testDeleteWithoutWorkflowItem()
    {
        $repository = $this->getRepository();

        $checkouts = $repository->findBy(['deleted' => false]);

        $this->deleteAllWorkflowItems();
        $repository->deleteWithoutWorkflowItem();

        $this->assertCount(count($checkouts), $repository->findBy(['deleted' => true]));
    }

    public function testFindByType()
    {
        $checkouts = $this->repository->findByPaymentMethod(LoadShoppingListsCheckoutsData::PAYMENT_METHOD);

        static::assertContains($this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_7), $checkouts);
    }

    protected function deleteAllWorkflowItems()
    {
        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(WorkflowItem::class);
        $repository = $manager->getRepository(WorkflowItem::class);

        $workflowItems = $repository->findAll();

        foreach ($workflowItems as $workflowItem) {
            $manager->remove($workflowItem);
        }

        $manager->flush();
    }

    public function testGetRelatedEntitiesCount()
    {
        $customerUser = $this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL);

        self::assertSame(3, $this->repository->getRelatedEntitiesCount($customerUser));
    }

    public function testGetRelatedEntitiesCountZero()
    {
        $customerUserWithoutRelatedEntities = $this->getReference(LoadCustomerUserData::LEVEL_1_1_EMAIL);

        self::assertSame(0, $this->repository->getRelatedEntitiesCount($customerUserWithoutRelatedEntities));
    }

    public function testResetCustomerUserForSomeEntities()
    {
        $customerUser = $this->getContainer()
            ->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadAuthCustomerUserData::AUTH_USER]);

        $this->getRepository()->resetCustomerUser($customerUser, [
            $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_1),
            $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_2),
        ]);

        $checkouts = $this->getRepository()->findBy(['customerUser' => null]);
        $this->assertCount(2, $checkouts);
    }

    public function testResetCustomerUser()
    {
        $customerUser = $this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL);
        $this->getRepository()->resetCustomerUser($customerUser);

        $checkouts = $this->getRepository()->findBy(['customerUser' => null]);
        $this->assertCount(5, $checkouts);
    }

    public function testFindWithInvalidSubtotals()
    {
        /** @var CheckoutSubtotal $subtotal1 */
        $subtotal1 = $this->getReference(LoadCheckoutSubtotals::CHECKOUT_SUBTOTAL_1);
        /** @var CheckoutSubtotal $subtotal2 */
        $subtotal2 = $this->getReference(LoadCheckoutSubtotals::CHECKOUT_SUBTOTAL_2);

        $subtotal1->setValid(false);
        $subtotal2->setValid(false);

        $em = $this->getContainer()->get('doctrine')->getManagerForClass(CheckoutSubtotal::class);
        $em->flush();

        $checkouts = $this->getRepository()->findWithInvalidSubtotals();
        $this->assertCount(2, $checkouts);
        $ids = [];
        foreach ($checkouts as $checkout) {
            $ids[] = $checkout->getId();
        }

        $this->assertContains($subtotal1->getCheckout()->getId(), $ids);
        $this->assertContains($subtotal2->getCheckout()->getId(), $ids);
    }
}
