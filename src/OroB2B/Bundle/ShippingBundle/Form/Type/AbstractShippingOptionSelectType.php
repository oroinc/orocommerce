<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider;

abstract class AbstractShippingOptionSelectType extends AbstractType
{
    const NAME = '';

    /** @var AbstractMeasureUnitProvider */
    protected $unitProvider;

    /**
     * @param AbstractMeasureUnitProvider $unitProvider
     */
    public function setUnitProvider(AbstractMeasureUnitProvider $unitProvider)
    {
        $this->unitProvider = $unitProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => function (Options $options) {
                    $codes = array_merge(
                        $this->unitProvider->getUnitsCodes(!$options['full_list']),
                        $options['additional_codes']
                    );

                    return $this->unitProvider->formatUnitsCodes(array_combine($codes, $codes), $options['compact']);
                },
                'compact' => false,
                'additional_codes' => [],
                'full_list' => false
            ]
        );
        $resolver->setAllowedTypes('compact', ['bool'])
            ->setAllowedTypes('additional_codes', ['array'])
            ->setAllowedTypes('full_list', ['bool']);
    }
}
