<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Client;

use GuzzleHttp\ClientInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Client\NVPClient;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Host\HostAddressProvider;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\NVP\EncoderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class NVPClientTest extends TestCase
{
    /** @var ClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $httpClient;

    /** @var EncoderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $encoder;

    /** @var NVPClient */
    protected $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->encoder = $this->createMock(EncoderInterface::class);
        $this->client = new NVPClient($this->httpClient, $this->encoder);
    }

    protected function tearDown(): void
    {
        unset($this->client, $this->encoder, $this->httpClient);
    }

    public function testSend()
    {
        $options = [];
        $encodedData = 'encoded[4]=data';
        $responseString = 'response=string';
        $responseArray = ['response' => 'string'];

        $this->encoder
            ->expects($this->once())
            ->method('encode')
            ->with($options)
            ->willReturn($encodedData);

        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($responseString);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                HostAddressProvider::PILOT_HOST_ADDRESS,
                [
                    'body' => $encodedData,
                    'verify' => true,
                ]
            )
            ->willReturn($response);

        $this->encoder
            ->expects($this->once())
            ->method('decode')
            ->with($responseString)
            ->willReturn($responseArray);

        $this->assertEquals($responseArray, $this->client->send(HostAddressProvider::PILOT_HOST_ADDRESS, $options));
    }

    /**
     * @return array
     */
    public function connectionOptionsDataProvider()
    {
        return [
            'default options' => [
                [],
                ['verify' => true, 'body' => ''],
            ],
            'disable ssl verify' => [
                ['SSL_VERIFY' => false],
                ['verify' => false, 'body' => ''],
            ],
            'enable ssl verify' => [
                ['SSL_VERIFY' => true],
                ['verify' => true, 'body' => ''],
            ],
            'pass proxy host only' => [
                [
                    'PROXY_HOST' => '12.23.34.45',
                ],
                [
                    'verify' => true,
                    'body' => '',
                ],
            ],
            'pass proxy port only' => [
                [
                    'PROXY_PORT' => 1234,
                ],
                [
                    'verify' => true,
                    'body'=> ''
                ],
            ],
            'pass proxy host and port both' => [
                [
                    'PROXY_HOST' => '12.23.34.45',
                    'PROXY_PORT' => 1234,
                ],
                [
                    'verify' => true,
                    'body'=> '',
                    'proxy' => '12.23.34.45:1234',
                ],
            ],
            'pass proxy host and port both with disabled ssl verification' =>[
                [
                    'SSL_VERIFY' => false,
                    'PROXY_HOST' => '12.23.34.45',
                    'PROXY_PORT' => 1234,
                ],
                [
                    'verify' => false,
                    'body'=> '',
                    'proxy' => '12.23.34.45:1234',
                ],
            ],
        ];
    }

    /**
     * @dataProvider connectionOptionsDataProvider
     */
    public function testConnectionOptionsArePassedToHttpClient(array $connectionOptions, array $expectedClientOptions)
    {
        $address = 'http://127.0.0.1';
        $options = [];
        $response = $this->createMock(ResponseInterface::class);

        $this->encoder
            ->expects($this->once())
            ->method('encode')
            ->with($options)
            ->willReturn('');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $address,
                $expectedClientOptions
            )
            ->willReturn($response);

        $this->client->send($address, $options, $connectionOptions);
    }
}
