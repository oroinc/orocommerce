<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Client;

use Guzzle\Http\ClientInterface;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Client\NVPClient;
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
}
