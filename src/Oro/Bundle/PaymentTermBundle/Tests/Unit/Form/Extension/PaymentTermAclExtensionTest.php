<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\PaymentTermBundle\Form\Extension\PaymentTermAclExtension;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PaymentTermAclExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentTermAclExtension */
    protected $extension;

    /** @var PaymentTermAssociationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTermAssociationProvider;

    /** @var PaymentTermAssociationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $authorizationChecker;

    protected function setUp()
    {
        $this->paymentTermAssociationProvider = $this->getMockBuilder(PaymentTermAssociationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->authorizationChecker = $this->getMock(AuthorizationCheckerInterface::class);

        $this->extension = new PaymentTermAclExtension(
            $this->paymentTermAssociationProvider,
            $this->authorizationChecker
        );
        $this->extension->setExtendedType('extended_type');
        $this->extension->setAclResource('acl_res');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Extended Type not configured
     */
    public function testGetExtendedTypeEmpty()
    {
        $this->extension->setExtendedType(null);
        $this->extension->getExtendedType();
    }

    public function testGetExtended()
    {
        $this->assertSame('extended_type', $this->extension->getExtendedType());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage ACL resource not configured
     */
    public function testBuildWithoutAclResource()
    {
        $this->extension->setAclResource(null);
        $builder = $this->getMock(FormBuilderInterface::class);

        $this->extension->buildForm($builder, []);
    }

    public function testBuildWithoutIsGrantedLeavePaymentTermFields()
    {
        $builder = $this->getMock(FormBuilderInterface::class);
        $this->authorizationChecker->expects($this->once())->method('isGranted')->willReturn(true);
        $this->paymentTermAssociationProvider->expects($this->never())->method($this->anything());

        $this->extension->buildForm($builder, []);
    }

    public function testBuildWithoutNotIsGrantedWithoitAssociations()
    {
        $builder = $this->getMock(FormBuilderInterface::class);
        $this->authorizationChecker->expects($this->once())->method('isGranted')->willReturn(false);

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')->willReturn([]);

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }

    public function testBuildWithoutNotIsGrantedShouldRemovePaymentTermFieldsWithoutField()
    {
        $builder = $this->getMock(FormBuilderInterface::class);
        $this->authorizationChecker->expects($this->once())->method('isGranted')->willReturn(false);

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $builder->expects($this->once())->method('has')->willReturn(false);
        $builder->expects($this->never())->method('remove');

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }

    public function testBuildWithoutNotIsGrantedShouldRemovePaymentTermFields()
    {
        $builder = $this->getMock(FormBuilderInterface::class);
        $this->authorizationChecker->expects($this->once())->method('isGranted')->willReturn(false);

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $builder->expects($this->once())->method('has')->willReturn(true);
        $builder->expects($this->once())->method('remove')->with('paymentTerm');

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }
}
