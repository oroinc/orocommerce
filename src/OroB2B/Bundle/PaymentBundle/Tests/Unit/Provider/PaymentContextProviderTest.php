<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\PaymentBundle\Provider\AddressExtractor;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider;

class PaymentContextProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var AddressExtractor|\PHPUnit_Framework_MockObject_MockObject */
    protected $extractor;

    /** @var PaymentContextProvider */
    protected $paymentContextProvider;

    protected function setUp()
    {
        $this->extractor = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\AddressExtractor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentContextProvider = new PaymentContextProvider($this->extractor);
    }

    protected function tearDown()
    {
        unset($this->paymentContextProvider, $this->extractor);
    }

    /**
     * @param mixed $context
     * @param mixed $entity
     * @param mixed $expected
     *
     * @dataProvider processContextDataProvider
     */
    public function testProcessContext($context, $entity, $expected)
    {
        $this->extractor->expects($this->any())->method('getCountryIso2')->willReturn('US');

        $this->assertEquals($expected, $this->paymentContextProvider->processContext($context, $entity));
    }

    /**
     * @return array
     */
    public function processContextDataProvider()
    {
        return [
            'empty context' => [[], new \stdClass(), []],
            'empty entity' => [['context'], null, []],
            'return entity and address' => [
                ['context'],
                new \stdClass(),
                ['entity' => new \stdClass(), 'country' => 'US'],
            ],
        ];
    }
}
