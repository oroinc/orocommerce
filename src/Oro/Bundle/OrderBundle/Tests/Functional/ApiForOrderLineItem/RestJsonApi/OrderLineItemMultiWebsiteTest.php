<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiForOrderLineItem\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class OrderLineItemMultiWebsiteTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroOrderBundle/Tests/Functional/DataFixtures/multiwebsite_products.yml'
        ]);
    }

    public function testCreate()
    {
        $this->post(
            ['entity' => 'orders'],
            'create_order_product_sku_multiwebsite.yml'
        );
    }
}
