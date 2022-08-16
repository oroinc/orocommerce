<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeAutocompleteType;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

abstract class AbstractCustomerTaxExtensionTest extends AbstractTaxExtensionTest
{
    /**
     * {@inheritDoc}
     */
    protected function createTaxCode(int $id = null): AbstractTaxCode
    {
        $taxCode = new CustomerTaxCode();
        if (null !== $id) {
            ReflectionUtil::setId($taxCode, $id);
        }

        return $taxCode;
    }

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
        $entity = $this->createTaxCodeTarget(1);
        $event = $this->createEvent($entity);
        $taxCode = $this->createTaxCode();

        $entity->expects($this->any())
            ->method('getTaxCode')
            ->willReturn($taxCode);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $taxCodeForm */
        $taxCodeForm = $event->getForm()->get('taxCode');
        $taxCodeForm->expects($this->once())
            ->method('setData')
            ->with($taxCode);

        $this->getExtension()->onPostSetData($event);
    }
}
