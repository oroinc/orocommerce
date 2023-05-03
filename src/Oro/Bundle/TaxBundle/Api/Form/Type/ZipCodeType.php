<?php

namespace Oro\Bundle\TaxBundle\Api\Form\Type;

use Oro\Bundle\ApiBundle\Form\DataTransformer\ResetTransformDataTransformer;
use Oro\Bundle\TaxBundle\Entity\ZipCode;
use Oro\Bundle\TaxBundle\Form\DataTransformer\ZipCodeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The API form type for the tax jurisdiction zip code.
 */
class ZipCodeType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new ResetTransformDataTransformer(new ZipCodeTransformer()));
        $builder
            ->add('from', TextType::class, ['property_path' => 'zipRangeStart'])
            ->add('to', TextType::class, ['property_path' => 'zipRangeEnd']);
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
}
