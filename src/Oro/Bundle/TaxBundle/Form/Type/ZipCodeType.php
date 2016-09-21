<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\TaxBundle\Form\DataTransformer\ZipCodeTransformer;

class ZipCodeType extends AbstractType
{
    const NAME = 'oro_tax_zip_code_type';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param $dataClass
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
        $builder->addModelTransformer(new ZipCodeTransformer());
        $builder
            ->add('zipRangeStart', 'text', [
                'required' => true,
            ])
            ->add('zipRangeEnd', 'text', [
                'required' => false,
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
