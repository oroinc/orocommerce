<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;
    const PRODUCT_SKU = 'SKU123';
    const PRODUCT_INVENTORY_QUANTITY = 100;

    /**
     * Example: And I fill integration fields with next data:
     * | Name               | Authorize                                                        |
     * | DefaultLabel       | authorize                                                        |
     * | ShortLabel         | au_sys                                                           |
     * | AllowedCreditCards | Visa                                                             |
     * | APiLoginId         | qwer1234                                                         |
     * | TransactionKey     | qwertyui12345678                                                 |
     * | ClientKey          | qwertyuiop1234567890qwertyuiop1234567890qwertyyuiop1234567890qwe |
     * | CVVRequiredEntry   | true                                                             |
     * | PaymentAction      | Authorize                                                        |
     * @Then I fill integration fields with next data:
     *
     * @param TableNode $table
     *
     * @return Form
     */
    public function fillIntegrationsFieldsWithNextData(TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement('AuNetForm');
        $form->fill($table);

        return $form;
    }

    /**
     * Example: And I fill credit card fields with next data:
     * | CreditCardNumber | 5555555555554444 |
     * | Month            | 11               |
     * | Year             | 19               |
     * | CVV              | 123              |
     * @Then I fill credit card fields with next data:
     *
     * @param TableNode $table
     *
     * @return Form
     */
    public function fillCreditCardFieldsWithNextData(TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement('CreditCardForm');
        $form->fill($table);

        return $form;
    }

    /**
     * @When click add payment method button
     */
    public function clickAddMethodButton()
    {
        $createOrderButton = $this->createElement('AddMethodButton');
        $createOrderButton->click();
    }

    /**
     * @Given There are products in the system available for order
     */
    public function setProductInventoryLevelQuantity()
    {
        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');

        /** @var Product $product */
        $product = $doctrineHelper->getEntityRepositoryForClass(Product::class)
            ->findOneBy(['sku' => self::PRODUCT_SKU]);

        $em = $doctrineHelper->getEntityManagerForClass(InventoryLevel::class);

        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $em->getRepository(InventoryLevel::class)->findOneBy(['product' => $product]);
        if (!$inventoryLevel) {
            /** @var InventoryManager $inventoryManager */
            $inventoryManager = $this->getContainer()->get('oro_inventory.manager.inventory_manager');
            $inventoryLevel = $inventoryManager->createInventoryLevel($product->getPrimaryUnitPrecision());
            $em->persist($inventoryLevel);
        }
        $inventoryLevel->setQuantity(self::PRODUCT_INVENTORY_QUANTITY);
        $em->flush();
    }
}
