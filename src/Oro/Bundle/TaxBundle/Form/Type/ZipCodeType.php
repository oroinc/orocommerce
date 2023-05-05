<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\TaxBundle\Entity\ZipCode;
use Oro\Bundle\TaxBundle\Form\DataTransformer\ZipCodeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for the tax jurisdiction zip code.
 */
class ZipCodeType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new ZipCodeTransformer());
        $builder
            ->add('zipRangeStart', TextType::class, ['required' => true])
            ->add('zipRangeEnd', TextType::class, ['required' => false]);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ZipCode::class
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_tax_zip_code_type';
    }
}
