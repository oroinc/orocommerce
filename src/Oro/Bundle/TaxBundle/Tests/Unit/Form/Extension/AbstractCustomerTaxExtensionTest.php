<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeAutocompleteType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

abstract class AbstractCustomerTaxExtensionTest extends AbstractTaxExtensionTest
{
    public function testBuildForm()
    {
        $customerTaxExtension = $this->getExtension();

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'taxCode',
                CustomerTaxCodeAutocompleteType::class,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'oro.tax.taxcode.label',
                    'create_form_route' => null,
                    'dynamic_fields_ignore_exception' => true
                ]
            );
        $builder->expects($this->exactly(2))
            ->method('addEventListener')
            ->withConsecutive(
                [FormEvents::POST_SET_DATA, [$customerTaxExtension, 'onPostSetData']],
                [FormEvents::POST_SUBMIT, [$customerTaxExtension, 'onPostSubmit'], 10]
            );

        $customerTaxExtension->buildForm($builder, []);
    }

    public function testOnPostSetDataExistingEntity()
    {
        $customer = $this->createTaxCodeTarget(1);
        $event = $this->createEvent($customer);

        $taxCode = $this->createTaxCode();
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $taxCodeForm */
        $taxCodeForm = $event->getForm()->get('taxCode');

        $customer->expects($this->any())
            ->method('getTaxCode')
            ->willReturn($taxCode);

        $taxCodeForm->expects($this->once())
            ->method('setData')
            ->with($taxCode);

        $this->getExtension()->onPostSetData($event);
    }

    /**
     * @param int|null $id
     * @return CustomerTaxCode
     */
    protected function createTaxCode($id = null)
    {
        return $this->getEntity(CustomerTaxCode::class, ['id' => $id]);
    }

    /**
     * Return testable collection of CustomerTaxCode
     *
     * @param CustomerTaxCode $customerTaxCode
     * @return Collection
     */
    abstract protected function getTestableCollection(CustomerTaxCode $customerTaxCode);
}
