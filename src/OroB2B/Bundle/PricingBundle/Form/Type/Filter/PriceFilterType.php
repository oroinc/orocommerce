<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class PriceFilterType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_filter';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $formatter;

    /**
     * @param TranslatorInterface $translator
     * @param ManagerRegistry           $registry
     * @param ProductUnitLabelFormatter $formatter
     */
    public function __construct(TranslatorInterface $translator, ManagerRegistry $registry, ProductUnitLabelFormatter $formatter)
    {
        $this->translator = $translator;
        $this->registry = $registry;
        $this->formatter = $formatter;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return NumberFilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            'unit',
            'choice',
            [
                'required' => true,
                'choices' => $this->getUnitChoices(),
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_type' => NumberFilterType::DATA_DECIMAL,
                'operator_choices'  => array(
                    NumberFilterType::TYPE_EQUAL         => $this->translator->trans('oro.filter.form.label_type_equal'),
                    NumberFilterType::TYPE_NOT_EQUAL     => $this->translator->trans('oro.filter.form.label_type_not_equal'),
                    NumberFilterType::TYPE_GREATER_EQUAL => $this->translator->trans('oro.filter.form.label_type_greater_equal'),
                    NumberFilterType::TYPE_GREATER_THAN  => $this->translator->trans('oro.filter.form.label_type_greater_than'),
                    NumberFilterType::TYPE_LESS_EQUAL    => $this->translator->trans('oro.filter.form.label_type_less_equal'),
                    NumberFilterType::TYPE_LESS_THAN     => $this->translator->trans('oro.filter.form.label_type_less_than'),
                ),
            ]
        );
    }

    /**
     * Get choices list for unit field.
     *
     * @return array
     */
    protected function getUnitChoices()
    {
        $choices = [];

        $units = $this->registry
            ->getManagerForClass('OroB2BProductBundle:ProductUnit')
            ->getRepository('OroB2BProductBundle:ProductUnit')
            ->findAll();

        foreach ($units as $unit) {
            $choices[$unit->getCode()] = $this->formatter->format($unit->getCode());
        }

        return $choices;
    }
}
