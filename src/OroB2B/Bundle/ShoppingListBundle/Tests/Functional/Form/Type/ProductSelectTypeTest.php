<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Autocomlete;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Form\Type\ScopedProductSelectTypeTest;

/**
 * @dbIsolation
 */
class ProductSelectTypeTest extends ScopedProductSelectTypeTest
{
    public function setUp()
    {
        $this->setDatagridIndexPath('oro_datagrid_index');
        $this->setSearchAutocompletePath('oro_form_autocomplete_search');

        $this->setDataParameters(['scope' => 'shopping_list']);

        $this->setConfigPath('oro_b2b_shopping_list.backend_product_visibility');

        parent::setUp();
    }
}
