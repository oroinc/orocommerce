<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Form\DataTransformer\QuantityTransformer;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Formats quantity value according to precision settings.
 */
class QuantityType extends AbstractType
{
    const NAME = 'oro_quantity';

    /**
     * @var NumberFormatter
     */
    private $numberFormatter;

    /** @var string */
    private $productClass;

    /**
     * @param NumberFormatter $numberFormatter
     * @param string          $productClass
     */
    public function __construct(NumberFormatter $numberFormatter, $productClass)
    {
        $this->numberFormatter = $numberFormatter;
        $this->productClass = $productClass;
    }

    /** {@inheritDoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Remove default transformer to avoid problems with two times reverse transformation
        $builder->resetViewTransformers();

        // Prepend number type transformer with quantity transformer for rounding
        $builder->addViewTransformer(
            new QuantityTransformer($this->numberFormatter, $options['useInputTypeNumberValueFormat'])
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'setDefaultData'], -1024);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'setDefaultData'], -1024);
    }

    public function setDefaultData(FormEvent $event)
    {
        $options = $event->getForm()->getConfig()->getOptions();

        $defaultData = $options['default_data'];
        if (!is_numeric($defaultData)) {
            return;
        }

        $data = $event->getData();
        if (!$data) {
            if ($defaultData === null) {
                $event->setData(null);
            } else {
                //number type expected string value in submitted data
                $event->setData((string)$defaultData);
            }
        }
    }

    /** {@inheritDoc} */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'default_data'                  => null,
                'constraints'                   => [new Range(['min' => 0]), new Decimal()],
                'useInputTypeNumberValueFormat' => false,
            ]
        );
        $resolver->addAllowedTypes('useInputTypeNumberValueFormat', 'bool');
    }

    /** {@inheritDoc} */
    public function getParent()
    {
        return NumberType::class;
    }

    /** {@inheritDoc} */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
