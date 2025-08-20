<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadGuestCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutAddressForVisitorWithoutGuestCheckoutTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            LoadCustomerData::class,
            LoadCustomerUserData::class,
            LoadGuestCheckoutData::class
        ]);
    }

    #[\Override]
    protected function postFixtureLoad(): void
    {
        parent::postFixtureLoad();
        $visitor = $this->getVisitor();
        $visitor->setCustomerUser($this->getReference('customer_user'));
        $guestCheckoutReferences = ['checkout.in_progress', 'checkout.deleted'];
        foreach ($guestCheckoutReferences as $checkoutReference) {
            /** @var Checkout $checkout */
            $checkout = $this->getReference($checkoutReference);
            $checkout->getSource()->getShoppingList()->addVisitor($visitor);
        }
        $this->getEntityManager()->flush();
    }

    public function testTryToGetList(): void
    {
        $response = $this->cget(['entity' => 'checkoutaddresses'], [], [], false);
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutaddresses', 'id' => '<toString(@checkout.in_progress.billing_address->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The guest checkout is disabled.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'checkoutaddresses'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The guest checkout is disabled.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdate(): void
    {
        $addressId = $this->getReference('checkout.in_progress.billing_address')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutaddresses', 'id' => (string)$addressId],
            [
                'data' => [
                    'type' => 'checkoutaddresses',
                    'id' => (string)$addressId,
                    'attributes' => [
                        'label' => 'Updated Address'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The guest checkout is disabled.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'checkoutaddresses', 'id' => '<toString(@checkout.in_progress.billing_address->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'checkoutaddresses'],
            ['filter[id]' => '<toString(@checkout.in_progress.billing_address->id)>'],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }

    public function testTryToGetSubresource(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'country'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'countries',
                    'id' => '<toString(@country_usa->iso2Code)>',
                    'attributes' => ['iso3Code' => 'USA']
                ]
            ],
            $response
        );
    }

    public function testTryToGetRelationship(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'country'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'countries',
                    'id' => '<toString(@country_usa->iso2Code)>'
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateRelationship(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'country'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationship(): void
    {
        $response = $this->postRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'country'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationship(): void
    {
        $response = $this->deleteRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'country'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
