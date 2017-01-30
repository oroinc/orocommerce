<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\OrderBundle\Tests\Behat\Element\Order;
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
     * @Then /^I should see an Order page with (?P<message>[\w\s\#]+)$/
     *
     * @param string $message
     */
    public function titleOfOrderPage($message)
    {
        /* @var $page Order */
        $page = $this->createElement('Order');
        $page->assertTitle($message);
    }

    /**
     * @param string $path
     * @return string
     */
    protected function getUrl($path)
    {
        return $this->getContainer()->get('router')->generate($path);
    }
}
