<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

use OroB2B\Bundle\TaxBundle\Form\DataTransformer\ZipCodeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ZipCodeType extends AbstractType
{
    const NAME = 'orob2b_tax_zip_code_type';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new ZipCodeTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
    }
}
