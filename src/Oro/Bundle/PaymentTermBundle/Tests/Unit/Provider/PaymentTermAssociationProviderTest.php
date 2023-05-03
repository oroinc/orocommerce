<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Tests\Unit\PaymentTermAwareStub;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

class PaymentTermAssociationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->paymentTermAssociationProvider = new PaymentTermAssociationProvider(
            $this->doctrineHelper,
            $this->configProvider,
            PropertyAccess::createPropertyAccessor()
        );
    }

    public function testGetDefaultAssociationName()
    {
        $this->assertEquals(
            'payment_term_7c4f1e8e',
            $this->paymentTermAssociationProvider->getDefaultAssociationName()
        );
    }

    public function testSetPaymentTermInWrongProperty()
    {
        $paymentTerm = new PaymentTerm();
        $aware = new PaymentTermAwareStub();
        $this->expectException(NoSuchPropertyException::class);
        $this->paymentTermAssociationProvider->setPaymentTerm($aware, $paymentTerm);
        $this->assertNull($aware->getPaymentTerm());
    }

    public function testSetPaymentTermSuccessful()
    {
        $paymentTerm = new PaymentTerm();
        $aware = new PaymentTermAwareStub();
        $this->paymentTermAssociationProvider->setPaymentTerm($aware, $paymentTerm, 'paymentTerm');
        $this->assertSame($paymentTerm, $aware->getPaymentTerm());
    }

    public function testGetPaymentTermInWrongProperty()
    {
        $paymentTerm = new PaymentTerm();
        $aware = new PaymentTermAwareStub($paymentTerm);
        $this->assertNull($this->paymentTermAssociationProvider->getPaymentTerm($aware));
    }

    public function testGetPaymentTermSuccessful()
    {
        $paymentTerm = new PaymentTerm();
        $aware = new PaymentTermAwareStub($paymentTerm);
        $this->assertEquals($paymentTerm, $this->paymentTermAssociationProvider->getPaymentTerm($aware, 'paymentTerm'));
    }

    public function testGetTargetField()
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('target_field')
            ->willReturn('target_field_val');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(\stdClass::class, 'fieldName')
            ->willReturn($config);

        $this->assertSame(
            'target_field_val',
            $this->paymentTermAssociationProvider->getTargetField(\stdClass::class, 'fieldName')
        );
    }

    public function testGetAssociationNamesDefaultShouldBeFirst()
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->willReturn($metadata);
        $metadata->expects($this->once())
            ->method('hasAssociation')
            ->willReturn(true);
        $metadata->expects($this->once())
            ->method('getAssociationsByTargetClass')
            ->willReturn([['fieldName' => 'field1'], ['fieldName' => 'field2']]);

        $this->assertEquals(
            ['payment_term_7c4f1e8e', 'field1', 'field2'],
            $this->paymentTermAssociationProvider->getAssociationNames(\stdClass::class)
        );
    }

    public function testGetAssociationNamesWithoutDefault()
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->willReturn($metadata);
        $metadata->expects($this->once())
            ->method('hasAssociation')
            ->willReturn(false);
        $metadata->expects($this->once())
            ->method('getAssociationsByTargetClass')
            ->willReturn([['fieldName' => 'field1'], ['fieldName' => 'field2']]);

        $this->assertEquals(
            ['field1', 'field2'],
            $this->paymentTermAssociationProvider->getAssociationNames(\stdClass::class)
        );
    }
}
