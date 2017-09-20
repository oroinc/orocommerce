<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Oro\Bundle\OrderBundle\Tests\Behat\Element\BackendOrder;
use Oro\Bundle\OrderBundle\Tests\Behat\Element\BackendOrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Behat\Element\CollectionTable;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

use Symfony\Bridge\Doctrine\ManagerRegistry;

class OrderContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @When /^I open Order History page on the store frontend$/
     */
    public function openOrderHistoryPage()
    {
        $this->visitPath($this->getUrl('oro_order_frontend_index'));
        $this->waitForAjax();
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function getUrl($path)
    {
        return $this->getContainer()->get('router')->generate($path);
    }

    /**
     * @Then /^I should see that order internal status is "([^"]*)"$/
     * @param string $status
     */
    public function iShouldSeeThatOrderInternalStatusIs($status)
    {
        self::assertContains($status, $this->getPage()->findLabel('Internal Status')->getParent()->getText(), '', true);
    }

    /**
     * @Given /^There is "([^"]*)" status for new orders in the system configuration$/
     * @param $statusName
     */
    public function thereIsStatusForNewOrdersInTheSystemConfiguration($statusName)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->getContainer()->get('oro_config.global');

        $status = $this->getInternalStatus($statusName);

        $configManager->set('oro_order.order_creation_new_internal_order_status', $status->getId());
        $configManager->flush();
    }

    /**
     * @param string $statusName
     *
     * @return AbstractEnumValue|object
     */
    protected function getInternalStatus($statusName)
    {
        /** @var ManagerRegistry $registry */
        $registry = $this->getContainer()->get('doctrine');
        $className = ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE);

        return $registry->getManagerForClass($className)->getRepository($className)->findOneBy(['name' => $statusName]);
    }

    //@codingStandardsIgnoreStart
    /**
     * Example: I click on Edit action for "Percent" row in "Discounts" table
     *
     * @Then /^(?:|I )click on (?P<actionName>[\w\s]+) action for "(?P<rowValue>[\w\s]+)" row in "(?P<elementName>[\w\s]+)" table/
     * @param string $actionName
     * @param string $rowValue
     * @param string $elementName
     */
    // @codingStandardsIgnoreEnd
    public function iClickOnActionForRowInTable($actionName, $rowValue, $elementName)
    {
        /** @var CollectionTable $table */
        $table = $this->createElement($elementName);

        static::assertInstanceOf(
            CollectionTable::class,
            $table,
            sprintf('Element should be of type %s', CollectionTable::class)
        );

        $table->clickActionLink($rowValue, $actionName);
    }

    /**
     * Example: I click on Edit action for "Percent" row in "Discounts" table
     *
     * @When /^(?:|I )click delete backend order line item "(?P<productSKU>[^"]+)"$/
     *
     * @param string $productSKU
     */
    public function clickDeleteBackendOrderLineItem($productSKU)
    {
        /** @var BackendOrder $order */
        $order = $this->createElement('BackendOrder');
        /** @var BackendOrderLineItem[] $lineItems */
        $lineItems = $order->getLineItems();

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getProductSKU() === $productSKU) {
                $lineItem->delete();
            }
        }
    }

    /**
     * Example: I set "2" Quantity for first backend order line item
     *
     * @When /^(?:|I )set "(?P<value>[\w\s]+)" (?P<field>[\w\s]+) for (?P<number>[\w\s]+) backend order line item$/
     *
     * @param string $value
     * @param string $field
     * @param string $number
     */
    public function setFieldValueForBackendOrderLineItem($value, $field, $number)
    {
        /** @var BackendOrder $order */
        $order = $this->createElement('BackendOrder');
        /** @var BackendOrderLineItem[] $lineItems */
        $lineItems = $order->getLineItems();

        $lineItemIndex = $this->getNumberFromString($number) - 1;
        self::assertArrayHasKey($lineItemIndex, $lineItems);

        $context = $lineItems[$lineItemIndex];
        $fieldElement = $context->find('named', ['field', $field]);
        if (!$fieldElement) {
            $fieldElement = $this->createElement($field, $context);
        }

        /** @var OroSelenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        $driver->typeIntoInput($fieldElement->getXpath(), $value);
    }

    /**
     * @param string $stringNumber
     * @return int
     */
    private function getNumberFromString($stringNumber)
    {
        switch (trim($stringNumber)) {
            case 'first':
                return 1;
            case 'second':
                return 2;
            default:
                return (int)$stringNumber;
        }
    }
}
