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

    public function testGetErrorMessageWithCommunicationError()
    {
        $resultValue = CommunicationErrorsStatusMap::FAILED_TO_CONNECT_TO_HOST;

        $response = new Response([Response::RESULT_KEY => $resultValue]);
        $expectedMessage = CommunicationErrorsStatusMap::getMessage($resultValue);

        $this->assertEquals($expectedMessage, $response->getErrorMessage());
    }
    public function testGetErrorMessageWithResponseStatusError()
    {
        $resultValue = ResponseStatusMap::GENERAL_ERROR;

        $response = new Response([Response::RESULT_KEY => $resultValue]);
        $expectedMessage = ResponseStatusMap::getMessage($resultValue);

        $this->assertEquals($expectedMessage, $response->getErrorMessage());
    }

    public function testGetErrorMessageWithResponseStatusApproved()
    {
        $resultValue = ResponseStatusMap::APPROVED;

        $response = new Response([Response::RESULT_KEY => $resultValue]);
        $expectedMessage = ResponseStatusMap::getMessage($resultValue);

        $this->assertEquals($expectedMessage, $response->getErrorMessage());
    }

    public function testGetData()
    {
        $data = ['input', 'array'];
        $response = new Response($data);
        $this->assertSame($data, $response->getData());
    }

    /**
     * @dataProvider getReferenceProvider
     * @param array $data
     * @param string $expected
     */
    public function testGetReference($data, $expected)
    {
        $response = new Response($data);
        $this->assertSame($expected, $response->getReference());
    }

    /**
     * @return array
     */
    public function getReferenceProvider()
    {
        return [
            [
                'data' => [Response::PNREF_KEY => 'reference'],
                'expected' => 'reference',
            ],
            [
                'data' => ['anotherKey' => 'value'],
                'expected' => null,
            ],
        ];
    }

    /**
     * @dataProvider getResultProvider
     * @param array $data
     * @param string $expected
     */
    public function testGetResult($data, $expected)
    {
        $response = new Response($data);
        $this->assertSame($expected, $response->getResult());
    }

    /**
     * @return array
     */
    public function getResultProvider()
    {
        return [
            [
                'data' => [Response::RESULT_KEY => 'RESULT'],
                'expected' => 'RESULT',
            ],
            [
                'data' => ['anotherKey' => 'value'],
                'expected' => null,
            ],
        ];
    }

    /**
     * @dataProvider getOffsetProvider
     * @param array $data
     * @param string $index
     * @param string $expected
     */
    public function testGetOffset($data, $index, $expected)
    {
        $response = new Response($data);
        $actual = $response->getOffset($index, 'defaultValue');
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function getOffsetProvider()
    {
        return [
            [
                'data' => ['key' => 'value'],
                'index' => 'key',
                'expected' => 'value',
            ],
            [
                'data' => ['key' => 'value'],
                'index' => 'anotherKey',
                'expected' => 'defaultValue',
            ],
        ];
    }
}
