<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Form\Type;

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

        $this->setDataParameters(['scope' => 'rfp']);

        $this->setConfigPath('oro_b2b_rfp.backend_product_visibility');

        parent::setUp();
    }
}
