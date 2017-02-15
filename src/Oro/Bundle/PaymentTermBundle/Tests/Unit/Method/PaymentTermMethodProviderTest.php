<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method;

use Monolog\Logger;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTermMethodProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Psr\Log\LoggerInterface;

class PaymentTermMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTermProvider;

    /**
     * @var PaymentTermAssociationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTermAssociationProvider;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PaymentTermMethodProvider
     */
    protected $provider;

    /**
     * @var PaymentTerm
     */
    protected $method;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->getMockBuilder(PaymentTermProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentTermAssociationProvider = $this->getMockBuilder(PaymentTermAssociationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->provider = new PaymentTermMethodProvider(
            $this->paymentTermProvider,
            $this->paymentTermAssociationProvider,
            $this->doctrineHelper,
            $this->logger
        );
        $this->method = new PaymentTerm(
            $this->paymentTermProvider,
            $this->paymentTermAssociationProvider,
            $this->doctrineHelper,
            $this->logger
        );
    }

    public function testGetPaymentMethods()
    {
        static::assertEquals([PaymentTerm::TYPE => $this->method], $this->provider->getPaymentMethods());
    }

    public function testGetPaymentMethod()
    {
        static::assertEquals($this->method, $this->provider->getPaymentMethod(PaymentTerm::TYPE));
    }

    public function testHasPaymentMethod()
    {
        static::assertTrue($this->provider->hasPaymentMethod(PaymentTerm::TYPE));
        static::assertFalse($this->provider->hasPaymentMethod('not_existing'));
    }
}
