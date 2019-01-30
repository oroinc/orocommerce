<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderAddressForBuyerTest extends FrontendRestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'orderaddresses']);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orderaddresses', 'id' => '<toString(@order1_billing_address->id)>'],
                    ['type' => 'orderaddresses', 'id' => '<toString(@order1_shipping_address->id)>']
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'orderaddresses', 'id' => '<toString(@order1_billing_address->id)>']
        );

        $this->assertResponseContains('get_order_address.yml', $response);
    }

    public function testTryToGetForChildCustomer()
    {
        $response = $this->get(
            ['entity' => 'orderaddresses', 'id' => '<toString(@order3_billing_address->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetForCustomerFromAnotherDepartment()
    {
        $response = $this->get(
            ['entity' => 'orderaddresses', 'id' => '<toString(@another_order_billing_address->id)>'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'orderaddresses'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'orderaddresses', 'id' => '<toString(@order1_discount_percent->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'orderaddresses', 'id' => '<toString(@order1_discount_percent->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'orderaddresses'],
            ['filter' => ['id' => '<toString(@order1_discount_percent->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForCountry()
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'country'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'countries', 'id' => '<toString(@country_usa->iso2Code)>']],
            $response
        );
    }

    public function testGetRelationshipForCountry()
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'country'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'countries', 'id' => '<toString(@country_usa->iso2Code)>']],
            $response
        );
    }

    public function testGetSubresourceForCountryForChildCustomer()
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order3_billing_address->id)>',
                'association' => 'country'
            ]
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testGetRelationshipForCountryForChildCustomer()
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order3_billing_address->id)>',
                'association' => 'country'
            ]
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToGetSubresourceForCountryForCustomerFromAnotherDepartment()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'orderaddresses',
                'id'          => '<toString(@another_order_billing_address->id)>',
                'association' => 'country'
            ],
            [],
            [],
            false
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToGetRelationshipForCountryForCustomerFromAnotherDepartment()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'orderaddresses',
                'id'          => '<toString(@another_order_billing_address->id)>',
                'association' => 'country'
            ]
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToUpdateRelationshipForCountry()
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'country'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForRegion()
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'region'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'regions', 'id' => '<toString(@region_usa_california->combinedCode)>']],
            $response
        );
    }

    public function testGetRelationshipForRegion()
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'region'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'regions', 'id' => '<toString(@region_usa_california->combinedCode)>']],
            $response
        );
    }

    public function testGetSubresourceForRegionForChildCustomer()
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order3_billing_address->id)>',
                'association' => 'region'
            ]
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testGetRelationshipForRegionForChildCustomer()
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order3_billing_address->id)>',
                'association' => 'region'
            ]
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToGetSubresourceForRegionForCustomerFromAnotherDepartment()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'orderaddresses',
                'id'          => '<toString(@another_order_billing_address->id)>',
                'association' => 'region'
            ],
            [],
            [],
            false
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToGetRelationshipForRegionForCustomerFromAnotherDepartment()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'orderaddresses',
                'id'          => '<toString(@another_order_billing_address->id)>',
                'association' => 'region'
            ]
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToUpdateRelationshipForRegion()
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'region'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForCustomerAddress()
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'customerAddress'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customeraddresses', 'id' => '<toString(@customer_address->id)>']],
            $response
        );
    }

    public function testGetRelationshipForCustomerAddress()
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'customerAddress'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customeraddresses', 'id' => '<toString(@customer_address->id)>']],
            $response
        );
    }

    public function testGetSubresourceForCustomerAddressForChildCustomer()
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order3_billing_address->id)>',
                'association' => 'customerAddress'
            ]
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testGetRelationshipForCustomerAddressForChildCustomer()
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order3_billing_address->id)>',
                'association' => 'customerAddress'
            ]
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToGetSubresourceForCustomerAddressForCustomerFromAnotherDepartment()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'orderaddresses',
                'id'          => '<toString(@another_order_billing_address->id)>',
                'association' => 'customerAddress'
            ],
            [],
            [],
            false
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToGetRelationshipForCustomerAddressForCustomerFromAnotherDepartment()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'orderaddresses',
                'id'          => '<toString(@another_order_billing_address->id)>',
                'association' => 'customerAddress'
            ]
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToUpdateRelationshipForCustomerAddress()
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'customerAddress'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForCustomerUserAddress()
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'customerUserAddress'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customeruseraddresses', 'id' => '<toString(@customer_user_address->id)>']],
            $response
        );
    }

    public function testGetRelationshipForCustomerUserAddress()
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'customerUserAddress'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customeruseraddresses', 'id' => '<toString(@customer_user_address->id)>']],
            $response
        );
    }

    public function testGetSubresourceForCustomerUserAddressForChildCustomer()
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order3_billing_address->id)>',
                'association' => 'customerUserAddress'
            ]
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testGetRelationshipForCustomerUserAddressForChildCustomer()
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order3_billing_address->id)>',
                'association' => 'customerUserAddress'
            ]
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToGetSubresourceForCustomerUserAddressForCustomerFromAnotherDepartment()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'orderaddresses',
                'id'          => '<toString(@another_order_billing_address->id)>',
                'association' => 'customerUserAddress'
            ],
            [],
            [],
            false
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToGetRelationshipForCustomerUserAddressForCustomerFromAnotherDepartment()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'orderaddresses',
                'id'          => '<toString(@another_order_billing_address->id)>',
                'association' => 'customerUserAddress'
            ]
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToUpdateRelationshipForCustomerUserAddress()
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderaddresses',
                'id' => '<toString(@order1_billing_address->id)>',
                'association' => 'customerUserAddress'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
