<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Form\Extension\CustomerFormExtension;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomerFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var CustomerFormExtension */
    private $extension;

    /** @var TranslatorInterface */
    private $translator;

    /** @var PaymentTermProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTermProvider;

    /** @var PaymentTermAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTermAssociationProvider;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(static function (string $key) {
                return sprintf('[trans]%s[/trans]', $key);
            });

        $this->paymentTermProvider = $this->createMock(PaymentTermProvider::class);
        $this->paymentTermAssociationProvider = $this->createMock(PaymentTermAssociationProvider::class);

        $this->extension = new CustomerFormExtension(
            $this->paymentTermProvider,
            $this->paymentTermAssociationProvider,
            $this->translator
        );
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([CustomerType::class], CustomerFormExtension::getExtendedTypes());
    }

    public function testBuildFormWithoutData()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $this->paymentTermAssociationProvider->expects($this->never())
            ->method($this->anything());
        $this->paymentTermProvider->expects($this->never())
            ->method($this->anything());

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormWithoutGroup()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn(new Customer());

        $this->paymentTermAssociationProvider->expects($this->never())
            ->method($this->anything());
        $this->paymentTermProvider->expects($this->never())
            ->method($this->anything());

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormWithoutAssociationNames()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn((new Customer())->setGroup(new CustomerGroup()));

        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([]);
        $this->paymentTermProvider->expects($this->never())
            ->method($this->anything());

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }

    public function testBuildFormPaymentTerm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn((new Customer())->setGroup(new CustomerGroup()));

        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $this->paymentTermProvider->expects($this->once())
            ->method('getCustomerGroupPaymentTerm')
            ->willReturn(null);

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }

    public function testBuildFormWithoutField()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn((new Customer())->setGroup(new CustomerGroup()));

        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $this->paymentTermProvider->expects($this->once())
            ->method('getCustomerGroupPaymentTerm')
            ->willReturn(new PaymentTerm());

        $builder->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }

    public function testBuildFormWithField()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn((new Customer())->setGroup(new CustomerGroup()));

        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $this->paymentTermProvider->expects($this->once())
            ->method('getCustomerGroupPaymentTerm')
            ->willReturn(new PaymentTerm());

        $builder->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $field = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('get')
            ->willReturn($field);
        $field->expects($this->once())
            ->method('getOptions')
            ->willReturn([]);
        $field->expects($this->once())
            ->method('getName')
            ->willReturn('name');
        $type = $this->createMock(ResolvedFormTypeInterface::class);
        $field->expects($this->once())
            ->method('getType')
            ->willReturn($type);
        $type->expects($this->once())
            ->method('getInnerType')
            ->willReturn(new FormType());

        $builder->expects($this->once())
            ->method('add')
            ->with(
                'name',
                FormType::class,
                ['configs' => ['placeholder' => '[trans]oro.paymentterm.customer.customer_group_defined[/trans]']]
            );

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }
}
