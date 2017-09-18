<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;
use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

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

    /**
     * @Then /^I should see next rows in "(?P<elementName>[\w\s]+)" table$/
     * @param TableNode $expectedTableNode
     * @param string $elementName
     */
    public function iShouldSeeNextRowsInTable(TableNode $expectedTableNode, $elementName)
    {
        $element = $this->createElement($elementName);
        $rows = array_map(function (NodeElement $element) {
            return $this->elementFactory->wrapElement(Table::TABLE_ROW_ELEMENT, $element);
        }, $element->findAll('css', 'tbody tr'));

        $expectedRows = $expectedTableNode->getRows();
        $headers = array_shift($expectedRows);
        $expectedCount = count($expectedRows);
        self::assertCount(
            $expectedCount,
            $rows,
            sprintf('Expects %s rows in table, but got %s', $expectedCount, count($rows))
        );

        foreach ($expectedRows as $rowKey => $expectedRow) {
            /** @var array $headers */
            foreach ($headers as $headerKey => $header) {
                $value = $rows[$rowKey]->getCellValue($header);
                self::assertEquals(
                    $expectedRow[$headerKey],
                    $value,
                    sprintf(
                        'Expect that row #%s contains "%s", but got "%s"',
                        $rowKey+1,
                        $expectedRow[$headerKey],
                        $value
                    )
                );
            }
        }
    }

    //@codingStandardsIgnoreStart
    /**
     * @Then /^I click on (?P<actionName>[\w\s]+) action for "(?P<rowValue>[\w\s]+)" row in "(?P<elementName>[\w\s]+)" table/
     * @param string $actionName
     * @param string $rowValue
     * @param string $elementName
     */
    // @codingStandardsIgnoreEnd
    public function iClickOnActionForRowInTable($actionName, $rowValue, $elementName)
    {
        $element = $this->createElement($elementName);

        // TODO: change this logic without 'getParent()' method for getting needed row
        $rowElement = $element->find('named', ['content', $rowValue]);
        if (!$rowElement) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find row with "%s" content',
                $rowValue
            ));
        }
        $rowElement = $rowElement->getParent();

        $action = $rowElement->find('css', sprintf('td.action > a[data-role="%s"]', strtolower($actionName)));
        if (!$action) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find "%s" action',
                $actionName
            ));
        }
        $action->click();
    }

    /**
     * @When /^(?:|I )fill in Discount Amount field with "(?P<value>(?:[^"]|\\")*)"$/
     * @param string $value
     */
    public function fillInDiscountAmountFieldWith($value)
    {
        $discountAmountField = $this->createElement('DiscountAmount');
        $discountAmountField->focus();
        $discountAmountField->setValue($value);
        $discountAmountField->blur();
    }
}
