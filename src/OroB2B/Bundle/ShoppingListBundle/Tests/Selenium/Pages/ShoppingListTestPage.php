<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

class ShoppingListTestPage extends AbstractPage
{

    /**
     * @var string
     */
    private $firstViewDetailsLink = '//*[@id="order-widget-dropdown"]//a[1]';

    /**
     * @var string
     */
    private $editIcon =
        "//*[@data-role='start-editing']";

    /**
     * @var
     */
    private $inlineEditInput =
        "//*[@id='title-inline-editable']//input";

    /**
     * @var string
     */
    private $submitButton =
        "//button[@type='submit']";

    /**
     * @var string
     */
    private $sidebarLabelElement =
        "//*[@data-bound-component='orob2bshoppinglist/js/app/views/shoppinglist-sidebar-view']//h3[1]";

    /**
     * @var string
     */
    private $widgetLabelElement =
        '//*[@id="order-widget-dropdown"]//span[1]';

    public function login()
    {

        $this->test->url('/account/user/login');
        $this->waitPageToLoad();
        $this->waitForAjax();

        $this->getTest()->byId('userNameSignIn')->clear();
        $this->getTest()->byId('userNameSignIn')->value('AmandaRCole@example.org');

        $this->getTest()->byId('passwordSignIn')->clear();
        $this->getTest()->byId('passwordSignIn')->value('AmandaRCole@example.org');

        $this->getTest()->byXPath("//input[@type='submit']")->click();

        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    public function selectFirstShoppingList()
    {
        $this->getTest()->byCssSelector('.shopping-lists-frontend-widget')->click();
        $this->getTest()
             ->byXPath($this->firstViewDetailsLink)
             ->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
    }

    public function editShoppingListName()
    {
        $this->getTest()
             ->byXPath($this->editIcon)
             ->click();

        sleep(0.5);

        $this->getTest()
             ->byXPath($this->inlineEditInput)
             ->clear();

        $this->getTest()
             ->byXPath($this->inlineEditInput)
             ->value('Renamed new shopping list');

        $this->getTest()
             ->byXPath($this->submitButton)
             ->click();

        sleep(0.5);

        $this->waitForAjax();
    }

    public function checkSidebarShoppingListName()
    {
        $element = $this->getTest()
                        ->byXPath($this->sidebarLabelElement);

        $this->getTest()->assertTrue(
            !empty($element),
            'Sidebar view does not exist'
        );

        $this->getTest()->assertEquals(
            strtoupper('Renamed new shopping list'), // text is transformed
            $element->text()
        );
    }

    public function checkWidgetShoppingListName()
    {
        $this->getTest()->byCssSelector('.shopping-lists-frontend-widget')->click();
        
        $element = $this->getTest()
                        ->byXPath($this->widgetLabelElement);

        $this->getTest()->assertTrue(
            !empty($element),
            'Widget does not exist'
        );

        $this->getTest()->assertEquals(
            strtoupper('Renamed new shopping list'), // text is transformed
            $element->text()
        );
    }
}
