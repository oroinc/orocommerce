<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PaymentBundle\Provider\AddressExtractor;
use Oro\Bundle\PaymentBundle\Provider\PaymentContextProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;

class PaymentContextProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var AddressExtractor|\PHPUnit_Framework_MockObject_MockObject */
    protected $extractor;

    /** @var PaymentContextProvider */
    protected $paymentContextProvider;

    /** @var UserCurrencyManager */
    protected $currencyManager;

    protected function setUp()
    {
        $this->extractor = $this->getMockBuilder(AddressExtractor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->currencyManager = $this->getMockBuilder(UserCurrencyManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentContextProvider = new PaymentContextProvider($this->extractor, $this->currencyManager);
    }

    protected function tearDown()
    {
        unset($this->paymentContextProvider, $this->extractor);
    }

    /**
     * @param object|null $entity
     * @param array $expected
     *
     * @dataProvider processContextDataProvider
     */
    public function testProcessContext($entity, $expected)
    {
        $this->extractor->expects($this->any())->method('getCountryIso2')->willReturn('US');

        $this->assertEquals($expected, $this->paymentContextProvider->processContext($entity));
    }

    /**
     * @return array
     */
    public function processContextDataProvider()
    {
        return [
            'empty entity' => [null, []],
            'return entity and address' => [
                new \stdClass(),
                ['entity' => new \stdClass(), 'country' => 'US', 'currency' => null],
            ],
        ];
    }
}
