<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ShoppingListTotalRepositoryTest extends WebTestCase
{
    /**
     * @var Registry
     */
    protected $registry;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->registry = $this->getContainer()->get('doctrine');
        $this->loadFixtures(
            [
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
            ]
        );
    }
    
    public function testInvalidateByCpl()
    {
        $invalidTotal = new ShoppingListTotal();
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $invalidTotal->setShoppingList($shoppingList);
        $invalidTotal->setValid(true);
        $invalidTotal->setCurrency('USD');
        $invalidTotal->setSubtotalValue(1);
        
        $manager = $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal');
        $manager->persist($invalidTotal);
        $manager->flush();

        $cpl = $this->getReference('1f');
        $manager->getRepository('OroB2BShoppingListBundle:ShoppingListTotal')
            ->invalidateByCpl([$cpl->getId()]);

        $manager->refresh($invalidTotal);
        $this->assertFalse($invalidTotal->isValid());
    }

    public function testInvalidateByAccountUsers()
    {
        $manager = $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal');
        /** @var ShoppingListTotalRepository $repository */
        $repository = $manager->getRepository('OroB2BShoppingListBundle:ShoppingListTotal');

        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $shoppingListTotal = $repository->findOneBy(['shoppingList' => $shoppingList->getId()]);
        $shoppingListTotal->setValid(true);
        $manager->flush();

        $repository->invalidateByAccounts(
            [$shoppingListTotal->getShoppingList()->getAccount()->getId()],
            $shoppingListTotal->getShoppingList()->getWebsite()
        );

        $manager->refresh($shoppingListTotal);
        $this->assertFalse($shoppingListTotal->isValid());
    }
}
