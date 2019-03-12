<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
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
    /**
     * @var CheckoutRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->setCurrentWebsite('default');
        $this->loadFixtures(
            [
                LoadShoppingListsCheckoutsData::class,
                LoadCustomerUserData::class,
            ]
        );

        $this->repository = $this->getRepository();
    }

    /**
     * @return CheckoutRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroCheckoutBundle:Checkout');
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

    public function testFindCheckoutByCustomerUserAndSourceCriteriaByShoppingList()
    {
        $customerUser = $this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL);
        $criteria = ['shoppingList' => $this->getReference(LoadShoppingLists::SHOPPING_LIST_7)];

        $this->assertSame(
            $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_7),
            $this->getRepository()->findCheckoutByCustomerUserAndSourceCriteria(
                $customerUser,
                $criteria,
                'b2b_flow_checkout'
            )
        );
    }

    public function testFindCheckoutBySourceCriteriaByShoppingList()
    {
        $criteria = ['shoppingList' => $this->getReference(LoadShoppingLists::SHOPPING_LIST_7)];

        $this->assertSame(
            $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_7),
            $this->getRepository()->findCheckoutBySourceCriteria(
                $criteria,
                'b2b_flow_checkout'
            )
        );
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

        self::assertSame(1, $this->repository->getRelatedEntitiesCount($customerUser));
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
        $this->assertCount(3, $checkouts);
    }
}
