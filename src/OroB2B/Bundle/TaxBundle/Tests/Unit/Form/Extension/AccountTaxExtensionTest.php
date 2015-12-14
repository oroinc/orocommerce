<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountType;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Form\Type\AccountTaxCodeAutocompleteType;
use OroB2B\Bundle\TaxBundle\Form\Extension\AccountTaxExtension;

class AccountTaxExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var AccountTaxCodeRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityRepository;

    /**
     * @var AccountTaxExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new AccountTaxExtension($this->doctrineHelper);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(AccountType::NAME, $this->extension->getExtendedType());
    }

    /**
     * @param bool $expectsManager
     * @param bool $expectsRepository
     */
    protected function prepareDoctrineHelper($expectsManager = false, $expectsRepository = false)
    {
        $entityManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $entityManager->expects($expectsManager ? $this->once() : $this->never())
            ->method('flush');

        $this->doctrineHelper->expects($expectsManager ? $this->once() : $this->never())
            ->method('getEntityManager')
            ->with('OroB2BTaxBundle:AccountTaxCode')
            ->willReturn($entityManager);

        $this->entityRepository = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($expectsRepository ? $this->once() : $this->never())
            ->method('getEntityRepository')
            ->with('OroB2BTaxBundle:AccountTaxCode')
            ->willReturn($this->entityRepository);
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'taxCode',
                AccountTaxCodeAutocompleteType::NAME,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.tax.accounttaxcode.entity_label'
                ]
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

    public function testOnPostSetDataNoAccount()
    {
        $this->prepareDoctrineHelper();

        $event = $this->createEvent(null);

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSetDataNewAccount()
    {
        $this->prepareDoctrineHelper();

        $event = $this->createEvent($this->createAccount());

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSetDataExistingAccount()
    {
        $this->prepareDoctrineHelper(false, true);

        $account = $this->createAccount(1);
        $event = $this->createEvent($account);

        $taxCode = $this->createTaxCode();

        $this->entityRepository->expects($this->once())
            ->method('findOneByAccount')
            ->with($account)
            ->willReturn($taxCode);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $taxCodeForm */
        $taxCodeForm = $event->getForm()->get('taxCode');
        $taxCodeForm->expects($this->once())
            ->method('setData')
            ->with($taxCode);

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSubmitNoAccount()
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
        $event = $this->createEvent($this->createAccount());
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $taxCodeForm */
        $taxCodeForm = $mainForm->get('taxCode');
        $taxCodeForm->expects($this->never())
            ->method('getData');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitNewAccount()
    {
        $this->prepareDoctrineHelper(true, true);

        $account = $this->createAccount();
        $event   = $this->createEvent($account);

        $taxCode = $this->createTaxCode(1);

        $this->assertTaxCodeAdd($event, $taxCode);
        $this->entityRepository->expects($this->once())
            ->method('findOneByAccount');

        $this->extension->onPostSubmit($event);

        $this->assertEquals([$account], $taxCode->getAccounts()->toArray());
    }

    public function testOnPostSubmitExistingAccount()
    {
        $this->prepareDoctrineHelper(true, true);

        $account = $this->createAccount(1);
        $event   = $this->createEvent($account);

        $newTaxCode         = $this->createTaxCode(1);
        $taxCodeWithAccount = $this->createTaxCode(2);
        $taxCodeWithAccount->addAccount($account);

        $this->assertTaxCodeAdd($event, $newTaxCode);
        $this->entityRepository->expects($this->once())
            ->method('findOneByAccount')
            ->will($this->returnValue($taxCodeWithAccount));

        $this->extension->onPostSubmit($event);

        $this->assertEquals([$account], $newTaxCode->getAccounts()->toArray());
        $this->assertEquals([], $taxCodeWithAccount->getAccounts()->toArray());
    }

    /**
     * @param mixed $data
     *
     * @return FormEvent
     */
    protected function createEvent($data)
    {
        $taxCodeForm = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects($this->any())
            ->method('get')
            ->with('taxCode')
            ->willReturn($taxCodeForm);

        return new FormEvent($mainForm, $data);
    }

    /**
     * @param int|null $id
     *
     * @return Account
     */
    protected function createAccount($id = null)
    {
        return $this->createEntity('OroB2B\Bundle\AccountBundle\Entity\Account', $id);
    }

    /**
     * @param int|null $id
     *
     * @return AccountTaxCode
     */
    protected function createTaxCode($id = null)
    {
        return $this->createEntity('OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode', $id);
    }

    /**
     * @param          $class string
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
     * @param FormEvent $event
     * @param AccountTaxCode  $taxCode
     */
    protected function assertTaxCodeAdd(FormEvent $event, AccountTaxCode $taxCode)
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $taxCodeForm */
        $taxCodeForm = $mainForm->get('taxCode');
        $taxCodeForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($taxCode));
    }
}
