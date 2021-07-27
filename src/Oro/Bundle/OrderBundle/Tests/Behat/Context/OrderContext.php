<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Symfony\Component\Routing\RouterInterface;

class OrderContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    private ManagerRegistry $managerRegistry;

    private RouterInterface $router;

    private ConfigManager $configManager;

    public function __construct(
        ManagerRegistry $managerRegistry,
        RouterInterface $router,
        ConfigManager $configManager
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->router = $router;
        $this->configManager = $configManager;
    }

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
        return $this->router->generate($path);
    }

    /**
     * @Then /^I should see that order internal status is "([^"]*)"$/
     * @param string $status
     */
    public function iShouldSeeThatOrderInternalStatusIs($status)
    {
        static::assertStringContainsStringIgnoringCase(
            $status,
            $this->getPage()->findLabel('Internal Status')->getParent()->getText()
        );
    }

    /**
     * @Given /^There is "([^"]*)" status for new orders in the system configuration$/
     */
    public function thereIsStatusForNewOrdersInTheSystemConfiguration($statusName)
    {
        $status = $this->getInternalStatus($statusName);

        $this->configManager->set('oro_order.order_creation_new_internal_order_status', $status->getId());
        $this->configManager->flush();
    }

    /**
     * Fill form with data
     * Example: And fill order product row for SKU "Headlamp 001" with:
     *            | Quantity | 10   |
     *            | Price    | 30   |
     *
     * @When /^(?:|I )fill order product row for SKU "(?P<sku>(?:[^"]|\\")*)" with:$/
     */
    public function fillOrderProductRow(TableNode $table, $sku)
    {
        $rowXpath = sprintf(
            "//form[starts-with(@id, 'oro_order_type')]" .
            "//tr[.//td[@class='order-line-item-sku']//*[contains(text(),'%s')]]",
            $sku
        );
        $elementXpath = [
            'Quantity' => "//*[@data-name='field__quantity']",
            'Price' => "//*[@data-name='field__value']"
        ];

        foreach ($table->getRows() as $row) {
            [$label, $value] = $row;
            if (empty($elementXpath[$label])) {
                continue;
            }

            $elXpath = $rowXpath . $elementXpath[$label];
            $el = $this->getPage()->find('xpath', $elXpath);
            $el->setValue($value);
        }
    }

    /**
     * @param string $statusName
     *
     * @return AbstractEnumValue|object
     */
    protected function getInternalStatus($statusName)
    {
        $className = ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE);

        return $this->managerRegistry
            ->getManagerForClass($className)
            ->getRepository($className)
            ->findOneBy(['name' => $statusName]);
    }
}
