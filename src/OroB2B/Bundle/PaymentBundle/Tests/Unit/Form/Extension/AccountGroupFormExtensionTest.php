<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PaymentBundle\Form\Extension\AccountFormExtension;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;
use OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupType;
use OroB2B\Bundle\PaymentBundle\Form\Extension\AccountGroupFormExtension;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AccountGroupFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    const PAYMENT_TERM_CLASS = 'OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm';

    /** @var AccountFormExtension */
    protected $extension;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var PaymentTermRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($message) {
                    return $message . '_trans';
                }
            );

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupId = 1;
        $this->repository =
            $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository')
                ->disableOriginalConstructor()
                ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->with('OroB2BAccountBundle:AccountGroup', $accountGroupId)
            ->willReturn(new AccountGroup());

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(static::PAYMENT_TERM_CLASS)
            ->willReturn($this->repository);

        $this->extension = new AccountGroupFormExtension(
            $this->doctrineHelper,
            $this->translator,
            static::PAYMENT_TERM_CLASS
        );
    }

    protected function tearDown()
    {
        unset($this->translator, $this->extension, $this->doctrineHelper, $this->repository);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(AccountGroupType::NAME, $this->extension->getExtendedType());
    }

    public function testBuildForm()
    {
        $builder = $this->getMock('\Symfony\Component\Form\FormBuilderInterface');

        $options = [
            'label'    => 'orob2b.payment.paymentterm.entity_label',
            'required' => false,
            'mapped'   => false,
        ];

        $builder->expects($this->once())
            ->method('add')
            ->with(
                'paymentTerm',
                PaymentTermSelectType::NAME,
                $options
            );

        $builder->expects($this->exactly(2))
            ->method('addEventListener');

        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$this->extension, 'onPostSetData']);

        $builder->expects($this->at(2))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit'], 10);

        $this->extension->buildForm($builder, []);
    }

    public function testOnPostSetDataNoAccountGroup()
    {
        $event = $this->createEvent(null);

        $this->repository->expects($this->never())
            ->method('getOnePaymentTermByAccountGroup');

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSetDataNewAccountGroup()
    {
        $event = $this->createEvent($this->createAccountGroupEntity());

        $this->repository->expects($this->never())
            ->method('getOnePaymentTermByAccountGroup');

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSetDataExistingPaymentTerm()
    {
        $accountGroup = $this->createAccountGroupEntity(1);
        $event = $this->createEvent($accountGroup);

        $paymentTerm = $this->createPaymentTermEntity();

        $this->repository->expects($this->once())
            ->method('getOnePaymentTermByAccountGroup')
            ->with($accountGroup)
            ->willReturn($paymentTerm);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $paymentTermForm */
        $paymentTermForm = $event->getForm()->get('paymentTerm');
        $paymentTermForm->expects($this->once())
            ->method('setData')
            ->with($paymentTerm);

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSubmitNoPaymentTerm()
    {
        $event = $this->createEvent(null);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->never())
            ->method('isValid');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitInvalidForm()
    {
        $event = $this->createEvent($this->createAccountGroupEntity(1));
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $paymentTermForm */
        $paymentTermForm = $mainForm->get('paymentTerm');
        $paymentTermForm->expects($this->never())
            ->method('getData');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitNewPaymentTerm()
    {
        $accountGroup = $this->createAccountGroupEntity(1);
        $event = $this->createEvent($accountGroup);

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->createPaymentTermEntity(1);

        $this->assertPaymentTermAdd($event, $paymentTerm);
        $this->repository->expects($this->once())
            ->method('setPaymentTermToAccountGroup')
            ->with($accountGroup, $paymentTerm);

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitExistingPaymentTerm()
    {
        $accountGroup = $this->createAccountGroupEntity(1);
        $event = $this->createEvent($accountGroup);

        /** @var PaymentTerm $newPaymentTerm */
        $newPaymentTerm = $this->createPaymentTermEntity(1);

        $this->assertPaymentTermAdd($event, $newPaymentTerm);

        $this->repository->expects($this->once())
            ->method('setPaymentTermToAccountGroup')
            ->with($accountGroup, $newPaymentTerm);

        $this->extension->onPostSubmit($event);
    }

    /**
     * @param mixed $data
     *
     * @return FormEvent
     */
    protected function createEvent($data)
    {
        $paymentTermForm = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects($this->any())
            ->method('get')
            ->with('paymentTerm')
            ->willReturn($paymentTermForm);

        return new FormEvent($mainForm, $data);
    }

    /**
     * @param string $class
     * @param int|null $id
     *
     * @return object
     */
    protected function createEntity($class, $id = null)
    {
        $entity = new $class();
        if ($id) {
            $reflection = new \ReflectionProperty($class, 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($entity, $id);
        }

        return $entity;
    }

    /**
     * @param null|int $id
     * @return object
     */
    protected function createAccountGroupEntity($id = null)
    {
        return $this->createEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', $id);
    }

    /**
     * @param null|int $id
     * @return object
     */
    protected function createPaymentTermEntity($id = null)
    {
        return $this->createEntity('OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm', $id);
    }

    /**
     * @param FormEvent $event
     * @param PaymentTerm $paymentTerm
     */
    protected function assertPaymentTermAdd(FormEvent $event, PaymentTerm $paymentTerm)
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $paymentTermForm */
        $paymentTermForm = $mainForm->get('paymentTerm');
        $paymentTermForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($paymentTerm));
    }
}
