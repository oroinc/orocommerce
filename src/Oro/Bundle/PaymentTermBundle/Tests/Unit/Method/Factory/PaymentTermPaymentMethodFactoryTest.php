<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Factory\PaymentTermPaymentMethodFactory;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Psr\Log\LoggerInterface;

class PaymentTermPaymentMethodFactoryTest extends \PHPUnit_Framework_TestCase
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
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var PaymentTermPaymentMethodFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->createMock(PaymentTermProvider::class);
        $this->paymentTermAssociationProvider = $this->createMock(PaymentTermAssociationProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->factory = new PaymentTermPaymentMethodFactory(
            $this->paymentTermProvider,
            $this->paymentTermAssociationProvider,
            $this->doctrineHelper,
            $this->logger
        );
    }

    public function testCreate()
    {
        /** @var PaymentTermConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(PaymentTermConfigInterface::class);

        $method = new PaymentTerm(
            $this->paymentTermProvider,
            $this->paymentTermAssociationProvider,
            $this->doctrineHelper,
            $config
        );
        $method->setLogger($this->logger);

        $this->assertEquals($method, $this->factory->create($config));
    }
}
