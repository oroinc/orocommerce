<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Response;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\Response;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseStatusMap;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\CommunicationErrorsStatusMap;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testIsSuccessful()
    {
        $response = new Response([Response::RESULT_KEY => ResponseStatusMap::APPROVED]);
        $this->assertTrue($response->isSuccessful());

        $response = new Response([Response::RESULT_KEY => ResponseStatusMap::GENERAL_ERROR]);
        $this->assertFalse($response->isSuccessful());
    }

    public function testGetMessage()
    {
        $message = 'test message';
        $response = new Response([Response::RESPMSG_KEY => $message]);
        $this->assertEquals($message, $response->getMessage());
    }

    public function testGetErrorMessage()
    {
        $response = new Response([Response::RESULT_KEY => CommunicationErrorsStatusMap::FAILED_TO_CONNECT_TO_HOST]);
        $this->assertEquals(
            CommunicationErrorsStatusMap::getMessage(CommunicationErrorsStatusMap::FAILED_TO_CONNECT_TO_HOST),
            $response->getErrorMessage()
        );

        $response = new Response([Response::RESULT_KEY => ResponseStatusMap::GENERAL_ERROR]);
        $this->assertEquals(
            ResponseStatusMap::getMessage(ResponseStatusMap::GENERAL_ERROR),
            $response->getErrorMessage()
        );

        $response = new Response([Response::RESULT_KEY => ResponseStatusMap::APPROVED]);
        $this->assertEquals(
            ResponseStatusMap::getMessage(ResponseStatusMap::APPROVED),
            $response->getErrorMessage()
        );
    }
}
