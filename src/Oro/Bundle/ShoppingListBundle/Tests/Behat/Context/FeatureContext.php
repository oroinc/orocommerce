<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Oro\Bundle\RFPBundle\Tests\Behat\Element\RequestForQuote;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\ShoppingList as ShoppingListElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @When /^Buyer is on (?P<shoppingListLabel>[\w\s]+)$/
     *
     * @param string $shoppingListLabel
     */
    public function buyerIsOnShoppingList($shoppingListLabel)
    {
        $shoppingList = $this->getShoppingListByLabel($shoppingListLabel);
        $this->visitPath($this->getShoppingListViewUrl($shoppingList));
        $this->waitForAjax();

        /* @var $element ShoppingListElement */
        $element = $this->createElement('ShoppingList');
        $element->assertTitle($shoppingListLabel);
    }

    /**
     * @When There it Requested a quote
     */
    public function buyerIsRequestedAQuote()
    {
        $this->getSession()->getPage()->clickLink('Request Quote');
        $this->waitForAjax();

        /* @var $page RequestForQuote */
        $page = $this->createElement('RequestForQuote');
        $page->assertTitle('Request A Quote');
        $this->waitForAjax();

        $this->getSession()->getPage()->pressButton('Submit Request');
        $this->waitForAjax();
    }

    /**
     * @Then /^it on page Request For Quote and see message (?P<message>[\w\s]+)$/
     *
     * @param string $message
     */
    public function buyerIsViewRequestForQuote($message)
    {
        /* @var $page RequestForQuote */
        $page = $this->createElement('RequestForQuote');
        $page->assertTitle('Request For Quote');

        /* @var $element Element */
        $element = $this->findElementContains('RequestForQuoteFlashMessage', $message);
        $this->assertTrue($element->isValid(), sprintf('Title "%s", was not match to current title', $message));
    }

    /**
     * @param string $label
     * @return null|ShoppingList
     */
    protected function getShoppingListByLabel($label)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(ShoppingList::class)
            ->getRepository(ShoppingList::class)
            ->findOneBy(['label' => $label]);
    }

    /**
     * @param ShoppingList $shoppingList
     * @return string
     */
    protected function getShoppingListViewUrl(ShoppingList $shoppingList)
    {
        return $this->getContainer()
            ->get('router')
            ->generate('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()]);
    }
}
