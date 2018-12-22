<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Symfony\Component\HttpFoundation\Response;

class PaymentTermTest extends FrontendRestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadPaymentTermData::class
        ]);
    }

    public function testTryToGetList()
    {
        $response = $this->cget(
            ['entity' => 'paymentterms'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            ['title' => 'resource not accessible exception'],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGet()
    {
        $response = $this->get(
            ['entity' => 'paymentterms', 'id' => '<toString(@payment_term_test_data_net 10->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            ['title' => 'resource not accessible exception'],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'paymentterms'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            ['title' => 'resource not accessible exception'],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'paymentterms', 'id' => '<toString(payment_term_test_data_net 10->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            ['title' => 'resource not accessible exception'],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'paymentterms', 'id' => '<toString(payment_term_test_data_net 10->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            ['title' => 'resource not accessible exception'],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'paymentterms'],
            ['filter' => ['id' => '<toString(payment_term_test_data_net 10->id)>']],
            [],
            false
        );
        $this->assertResponseValidationError(
            ['title' => 'resource not accessible exception'],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }
}
