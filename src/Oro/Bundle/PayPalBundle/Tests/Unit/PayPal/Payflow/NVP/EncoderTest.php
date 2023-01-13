<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\NVP;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\NVP\Encoder;

class EncoderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Encoder */
    private $encoder;

    protected function setUp(): void
    {
        $this->encoder = new Encoder();
    }

    /**
     * @dataProvider encodeProvider
     */
    public function testEncode(array $source, string $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->encoder->encode($source));
    }

    /**
     * @dataProvider decodeProvider
     */
    public function testDecode(string $source, array $expectedResult)
    {
        $this->assertSame($expectedResult, $this->encoder->decode($source));
    }

    public function encodeProvider(): array
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

    public function decodeProvider(): array
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
