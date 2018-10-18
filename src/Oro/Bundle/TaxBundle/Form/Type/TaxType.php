<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroPercentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Tax entity
 */
class TaxType extends AbstractType
{
    const NAME = 'oro_tax_type';

    /**
     * This value is set to be slightly more than TaxationSettingsProvider::CALCULATION_SCALE.
     * We need to set the precision explicitly because the default precision is lower than CALCULATION_SCALE.
     * We also want the precision to be slightly higher than CALCULATION_SCALE because user input in the form
     * is trimmed to this precision after validation, and we don't want the user to loose their input data
     * (at least not all of it)
     */
    const TAX_RATE_FIELD_PRECISION = 8;

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

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'oro.tax.code.label',
                'required' => true
            ])
            ->add('description', TextareaType::class, [
                'label' => 'oro.tax.description.label',
                'required' => false
            ])
            ->add('rate', OroPercentType::class, [
                'label' => 'oro.tax.rate.label',
                'required' => true,
                'scale' => self::TAX_RATE_FIELD_PRECISION,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
