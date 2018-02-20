<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Selenium;

use Oro\Bundle\ShoppingListBundle\Tests\Selenium\Pages\ShoppingListTestPage;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

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
