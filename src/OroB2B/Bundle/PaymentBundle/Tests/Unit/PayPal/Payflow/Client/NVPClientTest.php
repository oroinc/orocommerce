<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Client;

use Guzzle\Http\ClientInterface;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Client\NVPClient;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Gateway;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\NVP\EncoderInterface;

class NVPClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $httpClient;

    /** @var EncoderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $encoder;

    /** @var NVPClient */
    protected $client;

    public function setUp()
    {
        $this->httpClient = $this->getMock('Guzzle\Http\ClientInterface');
        $this->encoder = $this->getMock('OroB2B\Bundle\PaymentBundle\PayPal\Payflow\NVP\EncoderInterface');
        $this->client = new NVPClient($this->httpClient, $this->encoder);
    }

    public function tearDown()
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

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $response
            ->expects($this->once())
            ->method('getBody')
            ->with(true)
            ->willReturn($responseString);

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $request
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(Gateway::PILOT_HOST_ADDRESS, [], $encodedData)
            ->willReturn($request);

        $this->encoder
            ->expects($this->once())
            ->method('decode')
            ->with($responseString)
            ->willReturn($responseArray);

        $this->assertEquals($responseArray, $this->client->send(Gateway::PILOT_HOST_ADDRESS, $options));
    }
}
