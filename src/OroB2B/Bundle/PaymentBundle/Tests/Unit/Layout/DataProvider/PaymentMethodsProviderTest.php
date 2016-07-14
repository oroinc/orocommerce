<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodsProvider;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider;

class PaymentMethodsProviderTest extends \PHPUnit_Framework_TestCase
{
    const METHOD = 'Method';
    use EntityTrait;

    /**
     * @var PaymentMethodViewRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodViewRegistry;

    /**
     * @var PaymentContextProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentContextProvider;

    /** @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    /**
     * @var PaymentMethodsProvider
     */
    protected $provider;

    /** @var array */
    protected $contextData = ['contextData' => 'data'];

    public function setUp()
    {
        $this->paymentMethodViewRegistry = $this->getMockBuilder(
            'OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentContextProvider = $this
            ->getMockBuilder('\OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentMethodRegistry = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry');

        $this->provider = new PaymentMethodsProvider(
            $this->paymentMethodViewRegistry,
            $this->paymentContextProvider,
            $this->paymentMethodRegistry
        );
    }

    public function testGetViewsEmpty()
    {
        $this->paymentContextProvider->expects($this->any())->method('processContext')->willReturn($this->contextData);

        $this->paymentMethodViewRegistry->expects($this->once())
            ->method('getPaymentMethodViews')
            ->with($this->contextData)
            ->willReturn([]);

        $entity = new \stdClass();
        $data = $this->provider->getViews($entity);
        $this->assertEmpty($data);
    }

    public function testGetViewsWithoutEntity()
    {
        $view = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface');
        $view->expects($this->once())->method('getLabel')->willReturn('label');
        $view->expects($this->once())->method('getBlock')->willReturn('block');
        $view->expects($this->once())
            ->method('getOptions')
            ->with($this->contextData)
            ->willReturn([]);

        $this->paymentMethodViewRegistry->expects($this->once())
            ->method('getPaymentMethodViews')
            ->willReturn(['payment' => $view]);

        $this->paymentContextProvider->expects($this->once())
            ->method('processContext')
            ->willReturn($this->contextData);

        $entity = new \stdClass();
        $data = $this->provider->getViews($entity);
        $this->assertEquals(['payment' => ['label' => 'label', 'block' => 'block', 'options' => []]], $data);
    }

    public function testGetViewsWithEntity()
    {
        $entity = new \stdClass();

        $context = ['entity' => $entity];
        $this->paymentContextProvider->expects($this->once())
            ->method('processContext')
            ->with($context, $this->identicalTo($entity))
            ->willReturn($context);

        $view = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface');
        $view->expects($this->once())->method('getOptions')->with($context);

        $this->paymentMethodViewRegistry->expects($this->once())->method('getPaymentMethodViews')->willReturn(
            ['payment' => $view]
        );

        $this->provider->getViews($entity);
    }

    /**
     * @dataProvider isPaymentMethodEnabledDataProvider
     */
    public function testIsPaymentMethodEnabled($expected)
    {
        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->once())
            ->method('isEnabled')
            ->willReturn($expected);

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->with(self::METHOD)
            ->willReturn($paymentMethod);

        $result = $this->provider->isPaymentMethodEnabled(self::METHOD);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function isPaymentMethodEnabledDataProvider()
    {
        return [
            ['expected' => true],
            ['expected' => false],
        ];
    }

    public function testIsPaymentMethodEnabledWithException()
    {
        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->will($this->throwException(new \InvalidArgumentException));

        $result = $this->provider->isPaymentMethodEnabled(self::METHOD);
        $this->assertFalse($result);
    }

    /**
     * @dataProvider isPaymentMethodApplicableDataProvider
     * @param bool $isEnabled
     * @param bool $isApplicable
     * @param bool $expected
     */
    public function testIsPaymentMethodApplicable($isEnabled, $isApplicable, $expected)
    {
        $this->paymentContextProvider->expects($this->any())->method('processContext')->willReturn($this->contextData);

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->once())->method('isEnabled')->willReturn($isEnabled);
        $paymentMethod->expects($isEnabled ? $this->once() : $this->never())
            ->method('isApplicable')
            ->with($this->contextData)
            ->willReturn($isApplicable);

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->with(self::METHOD)
            ->willReturn($paymentMethod);

        $this->assertEquals($expected, $this->provider->isPaymentMethodApplicable(self::METHOD, new \stdClass()));
    }

    /**
     * @return array
     */
    public function isPaymentMethodApplicableDataProvider()
    {
        return [
            [
                '$isEnabled' => true,
                '$isApplicable' => false,
                '$expected' => false,
            ],
            [
                '$isEnabled' => false,
                '$isApplicable' => false,
                '$expected' => false,
            ],
            [
                '$isEnabled' => false,
                '$isApplicable' => true,
                '$expected' => false,
            ],
            [
                '$isEnabled' => true,
                '$isApplicable' => true,
                '$expected' => true,
            ],
        ];
    }

    public function testIsPaymentMethodApplicableWithException()
    {
        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->will($this->throwException(new \InvalidArgumentException));

        $this->assertFalse($this->provider->isPaymentMethodApplicable(self::METHOD, new \stdClass()));
    }

    /**
     * @dataProvider hasApplicablePaymentMethodsDataProvider
     * @param bool $isEnabled
     * @param bool $isApplicable
     * @param bool $expected
     */
    public function testHasApplicablePaymentMethods($isEnabled, $isApplicable, $expected)
    {
        $this->paymentContextProvider->expects($this->any())->method('processContext')->willReturn($this->contextData);

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->once())->method('isEnabled')->willReturn($isEnabled);
        $paymentMethod->expects($isEnabled ? $this->once() : $this->never())
            ->method('isApplicable')
            ->with($this->contextData)
            ->willReturn($isApplicable);

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);

        $this->assertEquals($expected, $this->provider->hasApplicablePaymentMethods(new \stdClass()));
    }

    /**
     * @return array
     */
    public function hasApplicablePaymentMethodsDataProvider()
    {
        return [
            [
                '$isEnabled' => true,
                '$isApplicable' => false,
                '$expected' => false,
            ],
            [
                '$isEnabled' => false,
                '$isApplicable' => false,
                '$expected' => false,
            ],
            [
                '$isEnabled' => false,
                '$isApplicable' => true,
                '$expected' => false,
            ],
            [
                '$isEnabled' => true,
                '$isApplicable' => true,
                '$expected' => true,
            ],
        ];
    }
}
