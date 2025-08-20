<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadGuestCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutAddressForVisitorWithGuestCheckoutTest extends FrontendRestJsonApiTestCase
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

        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', true);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', false);
        $configManager->flush();

        parent::tearDown();
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

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'checkoutaddresses'], [], [], false);
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutaddresses', 'id' => '<toString(@checkout.in_progress.billing_address->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutaddresses',
                    'id' => '<toString(@checkout.in_progress.billing_address->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToGetForDeletedCheckout(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutaddresses', 'id' => '<toString(@checkout.deleted.billing_address->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetForUnaccessibleCheckout(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutaddresses', 'id' => '<toString(@checkout.unaccessible.billing_address->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
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
                'detail' => 'Use API resource to create or update a checkout.'
                    . ' A checkout address can be created only together with a checkout.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testUpdate(): void
    {
        $addressId = $this->getReference('checkout.in_progress.billing_address')->getId();
        $data = [
            'data' => [
                'type' => 'checkoutaddresses',
                'id' => (string)$addressId,
                'attributes' => [
                    'label' => 'Updated Address'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkoutaddresses', 'id' => (string)$addressId],
            $data
        );
        $this->assertResponseContains($data, $response);
    }

    public function testTryToUpdateForDeletedCheckout(): void
    {
        $addressId = $this->getReference('checkout.deleted.billing_address')->getId();
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
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateForUnaccessibleCheckout(): void
    {
        $addressId = $this->getReference('checkout.unaccessible.billing_address')->getId();
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
                'detail' => 'No access to the entity.'
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
