<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class PriceFilterType extends AbstractType
{
    const NAME = 'orob2b_product_price_filter';

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $formatter;

    /**
     * @param ObjectManager             $translator
     * @param ProductUnitLabelFormatter $formatter
     */
    public function __construct(ObjectManager $manager, ProductUnitLabelFormatter $formatter)
    {
        $this->manager = $manager;
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
        return FilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $operatorChoices = [];
        $units = $this->manager->getRepository('OroB2BProductBundle:ProductUnit')->findAll();
        foreach ($units as $unit) {
            $operatorChoices[$unit->getCode()] = $this->formatter->format($unit->getCode());
        }

        $resolver->setDefaults(
            [
                'field_type'        => 'number',
                'operator_choices'  => $operatorChoices,
                'formatter_options' => [],
            ]
        );
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $formatter = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL);
        $formatterOptions = [
            'decimals' => 2,
            'grouping' => true,
            'orderSeparator' => $formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL),
            'decimalSeparator' => $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL),
        ];

        $view->vars['formatter_options'] = array_merge($formatterOptions, $options['formatter_options']);
    }
}
