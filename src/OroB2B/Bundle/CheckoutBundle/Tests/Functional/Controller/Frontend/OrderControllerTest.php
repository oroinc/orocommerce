<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Controller;

use Oro\Component\Testing\WebTestCase;

class OrderControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
            ]
        );
    }

    public function testCheckoutGrid()
    {
        $response = $this->requestFrontendGrid(
            [
                'gridName' => 'frontend-products-grid',
                RequestProductHandler::CATEGORY_ID_KEY => $secondLevelCategory->getId(),
                RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY => $includeSubcategories,
            ]
        );
    }
}
