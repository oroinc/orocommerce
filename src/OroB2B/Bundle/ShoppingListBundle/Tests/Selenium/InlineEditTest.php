<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use OroB2B\Bundle\ShoppingListBundle\Tests\Selenium\Pages\ShoppingListTestPage;

/**
 * @dbIsolation
 */
class InlineEditTest extends Selenium2TestCase
{
    public function testSidebarAndWidgetChanges()
    {
        $page = new ShoppingListTestPage($this);
        $page->login();
        $page->selectFirstShoppingList();
        $page->editShoppingListName();
        $page->checkSidebarShoppingListName();
        $page->checkWidgetShoppingListName();
    }
}
