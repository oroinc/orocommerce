<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides common functionality for tax code form types.
 *
 * This base class defines the standard form structure for tax codes, including code and description fields.
 * Subclasses should extend this to create specific tax code forms (e.g., product tax codes, customer tax codes)
 * by setting the appropriate data class and adding any additional fields as needed.
 */
abstract class AbstractTaxCodeType extends AbstractType
{
    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', TextType::class, [
                'required' => true
            ])
            ->add('description', TextType::class, [
                'required' => false
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
        ]);
    }
}
