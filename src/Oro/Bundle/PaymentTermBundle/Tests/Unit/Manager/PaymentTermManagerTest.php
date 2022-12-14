<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Manager\PaymentTermManager;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;

class PaymentTermManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentTermManager */
    private $manager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var PaymentTermAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTermAssociationProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->paymentTermAssociationProvider = $this->createMock(PaymentTermAssociationProvider::class);

        $this->manager = new PaymentTermManager($this->doctrineHelper, $this->paymentTermAssociationProvider);
    }

    public function testGetReference()
    {
        $paymentTerm = new PaymentTerm();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($paymentTerm);

        $this->assertEquals($paymentTerm, $this->manager->getReference(1));
    }

    public function testGetAssociationNameDefaultIsMissing()
    {
        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getDefaultAssociationName')
            ->willReturn('default');
        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->assertEquals(null, $this->manager->getAssociationName(\stdClass::class));
    }

    public function testGetAssociationNameDefaultWithoutClass()
    {
        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getDefaultAssociationName')
            ->willReturn('default');
        $this->paymentTermAssociationProvider->expects($this->never())
            ->method('getAssociationNames');

        $this->assertEquals('default', $this->manager->getAssociationName());
    }

    public function testGetAssociationName()
    {
        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getDefaultAssociationName')
            ->willReturn('default');
        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['default']);

        $this->assertEquals('default', $this->manager->getAssociationName(\stdClass::class));
    }
}
