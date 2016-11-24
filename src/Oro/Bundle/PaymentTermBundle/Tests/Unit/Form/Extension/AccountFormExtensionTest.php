<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Form\Type\AccountType;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Form\Extension\AccountFormExtension;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;

use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTranslator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AccountFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var AccountFormExtension */
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

        $this->extension = new AccountFormExtension(
            $this->paymentTermProvider,
            $this->paymentTermAssociationProvider,
            $this->translator
        );
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(AccountType::NAME, $this->extension->getExtendedType());
    }

    public function testBuildFormWithoutData()
    {
        $builder = $this->getMock(FormBuilderInterface::class);
        $builder->expects($this->once())->method('getData')->willReturn(null);

        $this->paymentTermAssociationProvider->expects($this->never())->method($this->anything());
        $this->paymentTermProvider->expects($this->never())->method($this->anything());

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormWithoutGroup()
    {
        $builder = $this->getMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn(new Account());

        $this->paymentTermAssociationProvider->expects($this->never())->method($this->anything());
        $this->paymentTermProvider->expects($this->never())->method($this->anything());

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormWithoutAssociationNames()
    {
        $builder = $this->getMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn((new Account())->setGroup(new AccountGroup()));

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')->willReturn([]);
        $this->paymentTermProvider->expects($this->never())->method($this->anything());

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }

    public function testBuildFormPaymentTerm()
    {
        $builder = $this->getMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn((new Account())->setGroup(new AccountGroup()));

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $this->paymentTermProvider->expects($this->once())->method('getAccountGroupPaymentTerm')->willReturn(null);

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }

    public function testBuildFormWithoutField()
    {
        $builder = $this->getMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn((new Account())->setGroup(new AccountGroup()));

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $this->paymentTermProvider->expects($this->once())->method('getAccountGroupPaymentTerm')
            ->willReturn(new PaymentTerm());

        $builder->expects($this->once())->method('has')->willReturn(false);

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }

    public function testBuildFormWithField()
    {
        $builder = $this->getMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn((new Account())->setGroup(new AccountGroup()));

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $this->paymentTermProvider->expects($this->once())->method('getAccountGroupPaymentTerm')
            ->willReturn(new PaymentTerm());

        $builder->expects($this->once())->method('has')->willReturn(true);

        $field = $this->getMock(FormBuilderInterface::class);
        $builder->expects($this->once())->method('get')->willReturn($field);
        $field->expects($this->once())->method('getOptions')->willReturn([]);
        $field->expects($this->once())->method('getName')->willReturn('name');
        $type = $this->getMock(ResolvedFormTypeInterface::class);
        $field->expects($this->once())->method('getType')->willReturn($type);
        $type->expects($this->once())->method('getName')->willReturn('type');

        $builder->expects($this->once())->method('add')->with(
            'name',
            'type',
            ['configs' => ['placeholder' => '[trans]oro.paymentterm.account.account_group_defined[/trans]']]
        );

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }
}
