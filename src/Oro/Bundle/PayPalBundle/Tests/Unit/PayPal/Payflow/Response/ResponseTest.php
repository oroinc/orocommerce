<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Response;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\CommunicationErrorsStatusMap;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseStatusMap;

class ResponseTest extends \PHPUnit\Framework\TestCase
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
     */
    public function testGetReference(array $data, ?string $expected)
    {
        $response = new Response($data);
        $this->assertSame($expected, $response->getReference());
    }

    public function getReferenceProvider(): array
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
     */
    public function testGetResult(array $data, ?string $expected)
    {
        $response = new Response($data);
        $this->assertSame($expected, $response->getResult());
    }

    public function getResultProvider(): array
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
     */
    public function testGetOffset(array $data, string $index, string $expected)
    {
        $response = new Response($data);
        $actual = $response->getOffset($index, 'defaultValue');
        $this->assertSame($expected, $actual);
    }

    public function getOffsetProvider(): array
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
