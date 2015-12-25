<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupType;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Form\Extension\AccountGroupTaxExtension;
use OroB2B\Bundle\TaxBundle\Form\Type\AccountTaxCodeAutocompleteType;
use OroB2B\Bundle\TaxBundle\Form\Extension\AccountTaxExtension;

class AccountGroupTaxExtensionTest extends AbstractTaxExtensionText
{
    /**
     * @var AccountTaxCodeRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityRepository;

    /**
     * @return AccountTaxExtension
     */
    protected function getExtension()
    {
        return new AccountGroupTaxExtension($this->doctrineHelper, 'OroB2BTaxBundle:AccountTaxCode');
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(AccountGroupType::NAME, $this->getExtension()->getExtendedType());
    }

    /**
     * @param bool $expectsManager
     * @param bool $expectsRepository
     */
    protected function prepareDoctrineHelper($expectsManager = false, $expectsRepository = false)
    {
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
        $accountTaxExtension = $this->getExtension();

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
                    'label' => 'orob2b.tax.taxcode.form.extension.label',
                    'create_form_route' => null,
                ]
            );
        $builder->expects($this->exactly(2))
            ->method('addEventListener');
        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$accountTaxExtension, 'onPostSetData']);
        $builder->expects($this->at(2))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$accountTaxExtension, 'onPostSubmit'], 10);

        $accountTaxExtension->buildForm($builder, []);
    }

    public function testOnPostSetDataExistingAccountGroup()
    {
        $this->prepareDoctrineHelper(false, true);

        $account = $this->createTaxCodeTarget(1);
        $event = $this->createEvent($account);

        $taxCode = $this->createTaxCode();

        $this->entityRepository->expects($this->once())
            ->method('findOneByAccountGroup')
            ->with($account)
            ->willReturn($taxCode);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $taxCodeForm */
        $taxCodeForm = $event->getForm()->get('taxCode');
        $taxCodeForm->expects($this->once())
            ->method('setData')
            ->with($taxCode);

        $this->getExtension()->onPostSetData($event);
    }

    public function testOnPostSubmitNewAccountGroup()
    {
        $this->prepareDoctrineHelper(true, true);

        $account = $this->createTaxCodeTarget();
        $event = $this->createEvent($account);

        $taxCode = $this->createTaxCode(1);

        $this->assertTaxCodeAdd($event, $taxCode);
        $this->entityRepository->expects($this->once())
            ->method('findOneByAccountGroup');

        $this->getExtension()->onPostSubmit($event);

        $this->assertEquals([$account], $taxCode->getAccountGroups()->toArray());
    }

    public function testOnPostSubmitExistingAccount()
    {
        $this->prepareDoctrineHelper(true, true);

        $accountGroup = $this->createTaxCodeTarget(1);
        $event = $this->createEvent($accountGroup);

        $newTaxCode = $this->createTaxCode(1);
        $taxCodeWithAccountGroup = $this->createTaxCode(2);
        $taxCodeWithAccountGroup->addAccountGroup($accountGroup);

        $this->assertTaxCodeAdd($event, $newTaxCode);
        $this->entityRepository->expects($this->once())
            ->method('findOneByAccountGroup')
            ->will($this->returnValue($taxCodeWithAccountGroup));

        $this->getExtension()->onPostSubmit($event);

        $this->assertEquals([$accountGroup], $newTaxCode->getAccountGroups()->toArray());
        $this->assertEquals([], $taxCodeWithAccountGroup->getAccountGroups()->toArray());
    }

    /**
     * @param int|null $id
     *
     * @return AccountGroup
     */
    protected function createTaxCodeTarget($id = null)
    {
        return $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', ['id' => $id]);
    }

    /**
     * @param int|null $id
     *
     * @return AccountTaxCode
     */
    protected function createTaxCode($id = null)
    {
        return $this->getEntity('OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode', ['id' => $id]);
    }
}
