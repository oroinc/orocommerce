<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\NVP;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\NVP\Encoder;

class EncoderTest extends \PHPUnit_Framework_TestCase
{
    /** @var Encoder */
    protected $encoder;

    protected function setUp()
    {
        $this->encoder = new Encoder();
    }

    protected function tearDown()
    {
        unset($this->encoder);
    }

    /**
     * @dataProvider encodeProvider
     * @param array $source
     * @param string $expectedResult
     */
    public function testEncode($source, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->encoder->encode($source));
    }

    /**
     * @dataProvider decodeProvider
     * @param string $source
     * @param array $expectedResult
     */
    public function testDecode($source, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->encoder->decode($source));
    }

    /**
     * @return array
     */
    public function encodeProvider()
    {
        return [
            [
                'source' => [],
                'expectedResult' => ''
            ],
            [
                'source' => ['key1' => 'value1'],
                'expectedResult' => 'key1[6]=value1'
            ],
            [
                'source' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => '',
                    'key4' => ''
                ],
                'expectedResult' => 'key1[6]=value1&key2[6]=value2&key3[0]=&key4[0]='
            ],
        ];
    }

    /**
     * @return array
     */
    public function decodeProvider()
    {
        return [
            [
                'source' => '',
                'expectedResult' => []
            ],
            [
                'source' => 'key1[5]=value',
                'expectedResult' => ['key1' => 'value']
            ],
            [
                'source' => 'key1=value1&key2=value2',
                'expectedResult' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ]
            ],
            [
                'source' => 'key1[6]=value1&key2=value2',
                'expectedResult' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ]
            ],
            [
                'source' => 'key1[23]=value=with-some=symbols&key2=value2',
                'expectedResult' => [
                    'key1' => 'value=with-some=symbols',
                    'key2' => 'value2',
                ]
            ],
            [
                'source' => 'key1[6]=value1&key2[6]=value2&key3[0]=&key4[0]=',
                'expectedResult' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => '',
                    'key4' => '',
                ]
            ],
            [
                'source' => 'key1=value1&key2[6]=value2&key3=&key4[0]=&key5[23]=value=with-some=symbols',
                'expectedResult' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => '',
                    'key4' => '',
                    'key5' => 'value=with-some=symbols'
                ]
            ],
            'delayed capture' => [
                'source' => 'RESULT=0&PNREF=EQRB8A32CD6A&RESPMSG=Approved&AUTHCODE=00&TRACEID=1234567890' .
                    '&ACHSTATUS=A&HOSTCODE=07&TRANSTIME=2012-02-09 15:24:22',
                'expectedResult' => [
                    'RESULT' => '0',
                    'PNREF' => 'EQRB8A32CD6A',
                    'RESPMSG' => 'Approved',
                    'AUTHCODE' => '00',
                    'TRACEID' => '1234567890',
                    'ACHSTATUS' => 'A',
                    'HOSTCODE' => '07',
                    'TRANSTIME' => '2012-02-09 15:24:22',
                ],
            ],
        ];
    }
}
