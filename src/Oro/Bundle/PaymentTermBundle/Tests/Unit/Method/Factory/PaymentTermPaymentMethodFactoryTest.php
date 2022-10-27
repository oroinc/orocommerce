<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Factory\PaymentTermPaymentMethodFactory;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Psr\Log\LoggerInterface;

class PaymentTermPaymentMethodFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentTermProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentTermProvider;

    /**
     * @var PaymentTermAssociationProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentTermAssociationProvider;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var PaymentTermPaymentMethodFactory
     */
    private $factory;

    protected function setUp(): void
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
        /** @var PaymentTermConfigInterface|\PHPUnit\Framework\MockObject\MockObject $config */
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
