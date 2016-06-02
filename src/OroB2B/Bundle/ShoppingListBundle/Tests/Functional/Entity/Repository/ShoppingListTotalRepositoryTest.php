<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ShoppingListTotalRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
            ]
        );
    }
    
    public function testInvalidByCpl()
    {
        $registry = $this->getContainer()->get('doctrine');
        
        $invalidTotal = new ShoppingListTotal();
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $invalidTotal->setShoppingList($shoppingList);
        $invalidTotal->setValid(true);
        $invalidTotal->setCurrency('USD');
        $invalidTotal->setSubtotalValue(1);
        
        $manager = $registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal');
        $manager->persist($invalidTotal);
        $manager->flush();

        $cpl = $this->getReference('1f');
        $registry->getRepository('OroB2BShoppingListBundle:ShoppingListTotal')
            ->invalidateByCpl([$cpl->getId()]);

        $manager->refresh($invalidTotal);
        $this->assertFalse($invalidTotal->isValid());
    }
}
