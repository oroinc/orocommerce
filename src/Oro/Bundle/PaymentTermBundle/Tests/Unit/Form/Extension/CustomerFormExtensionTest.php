<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Form\Extension\CustomerFormExtension;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;

use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTranslator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CustomerFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var CustomerFormExtension */
    protected $extension;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTermProvider;

    /** @var PaymentTermAssociationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTermAssociationProvider;

    protected function setUp()
    {
        $this->translator = new StubTranslator();

        $this->paymentTermProvider = $this->getMockBuilder(PaymentTermProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTermAssociationProvider = $this->getMockBuilder(PaymentTermAssociationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new CustomerFormExtension(
            $this->paymentTermProvider,
            $this->paymentTermAssociationProvider,
            $this->translator
        );
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(CustomerType::NAME, $this->extension->getExtendedType());
    }

    public function testBuildFormWithoutData()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())->method('getData')->willReturn(null);

        $this->paymentTermAssociationProvider->expects($this->never())->method($this->anything());
        $this->paymentTermProvider->expects($this->never())->method($this->anything());

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormWithoutGroup()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn(new Customer());

        $this->paymentTermAssociationProvider->expects($this->never())->method($this->anything());
        $this->paymentTermProvider->expects($this->never())->method($this->anything());

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormWithoutAssociationNames()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn((new Customer())->setGroup(new CustomerGroup()));

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')->willReturn([]);
        $this->paymentTermProvider->expects($this->never())->method($this->anything());

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }

    public function testBuildFormPaymentTerm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn((new Customer())->setGroup(new CustomerGroup()));

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $this->paymentTermProvider->expects($this->once())->method('getCustomerGroupPaymentTerm')->willReturn(null);

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }

    public function testBuildFormWithoutField()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn((new Customer())->setGroup(new CustomerGroup()));

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $this->paymentTermProvider->expects($this->once())->method('getCustomerGroupPaymentTerm')
            ->willReturn(new PaymentTerm());

        $builder->expects($this->once())->method('has')->willReturn(false);

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }

    public function testBuildFormWithField()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn((new Customer())->setGroup(new CustomerGroup()));

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $this->paymentTermProvider->expects($this->once())->method('getCustomerGroupPaymentTerm')
            ->willReturn(new PaymentTerm());

        $builder->expects($this->once())->method('has')->willReturn(true);

        $field = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())->method('get')->willReturn($field);
        $field->expects($this->once())->method('getOptions')->willReturn([]);
        $field->expects($this->once())->method('getName')->willReturn('name');
        $type = $this->createMock(ResolvedFormTypeInterface::class);
        $field->expects($this->once())->method('getType')->willReturn($type);
        $type->expects($this->once())->method('getName')->willReturn('type');

        $builder->expects($this->once())->method('add')->with(
            'name',
            'type',
            ['configs' => ['placeholder' => '[trans]oro.paymentterm.customer.customer_group_defined[/trans]']]
        );

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }
}
