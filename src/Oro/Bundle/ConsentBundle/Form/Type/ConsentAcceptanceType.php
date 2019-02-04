<?php

namespace Oro\Bundle\ConsentBundle\Form\Type;

use Oro\Bundle\ConsentBundle\Form\DataTransformer\CustomerConsentsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents list of available consents for certain CustomerUser where checked options means accepted consents
 */
class ConsentAcceptanceType extends AbstractType
{
    const TARGET_FIELDNAME = 'customerConsents';

    /**
     * @var CustomerConsentsTransformer
     */
    private $transformer;

    /**
     * @param CustomerConsentsTransformer $transformer
     */
    public function __construct(CustomerConsentsTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'property_path' => 'acceptedConsents',
            'empty_data' => [],
            'label' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_customer_consents';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return HiddenType::class;
    }
}
