<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Tests\Unit\Model\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AlternativeCheckoutBundle\Model\Action\AlternativeCheckoutByQuote;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Action\Tests\Unit\Action\Stub\StubStorage;

class AlternativeCheckoutByQuoteTest extends \PHPUnit_Framework_TestCase
{
    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $registry;

    /** @var ContextAccessor */
    private $contextAccessor;

    /** @var AlternativeCheckoutByQuote */
    private $action;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->contextAccessor = new ContextAccessor();

        $this->action = new AlternativeCheckoutByQuote($this->contextAccessor, $this->registry);
        $this->action->setDispatcher($this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface'));
    }

    protected function tearDown()
    {
        unset($this->action, $this->registry, $this->contextAccessor);
    }

    public function testInitialize()
    {
        $options = [
            AlternativeCheckoutByQuote::QUOTE => new Quote(),
            AlternativeCheckoutByQuote::CHECKOUT_ATTRIBUTE => 'test'
        ];

        $this->assertInstanceOf(
            'Oro\Component\Action\Action\ActionInterface',
            $this->action->initialize($options)
        );

        $this->assertAttributeEquals($options, 'options', $this->action);
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     *
     * @param array $inputData
     * @param string $exception
     * @param string $exceptionMessage
     */
    public function testInitializeException(array $inputData, $exception, $exceptionMessage)
    {
        $this->setExpectedException($exception, $exceptionMessage);

        $this->action->initialize($inputData);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        return [
            [
                'inputData' => [
                    AlternativeCheckoutByQuote::QUOTE => null,
                    AlternativeCheckoutByQuote::CHECKOUT_ATTRIBUTE => 'test'
                ],
                'expectedException' => 'Oro\Component\Action\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Parameter `quote` is required'
            ],
            [
                'inputData' => [
                    AlternativeCheckoutByQuote::QUOTE => new Quote(),
                    AlternativeCheckoutByQuote::CHECKOUT_ATTRIBUTE => null
                ],
                'expectedException' => 'Oro\Component\Action\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Parameter `checkout` is required'
            ]
        ];
    }

    public function testExecuteMethod()
    {
        $quote = new Quote();
        $checkout = new Checkout();

        $data = new StubStorage(['param' => 'value']);
        $options = [
            AlternativeCheckoutByQuote::QUOTE => $quote,
            AlternativeCheckoutByQuote::CHECKOUT_ATTRIBUTE => 'test'
        ];

        $this->assertRepositoryCalled($quote, $checkout);

        $this->action->initialize($options);
        $this->action->execute($data);

        $this->assertSame(['param' => 'value', 'test' => $checkout], $data->getValues());
    }

    /**
     * @param Quote $quote
     * @param Checkout $checkout
     */
    private function assertRepositoryCalled(Quote $quote, Checkout $checkout)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|CheckoutRepository $repository */
        $repository = $this->getMockBuilder(CheckoutRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())
            ->method('getCheckoutByQuote')
            ->with($quote)
            ->willReturn($checkout);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $em */
        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroCheckoutBundle:Checkout')
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroCheckoutBundle:Checkout')
            ->willReturn($em);
    }
}
