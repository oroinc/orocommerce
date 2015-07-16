<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class PriceFilterType extends AbstractType
{
    const NAME = 'orob2b_product_price_filter';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $formatter;

    /**
     * @param ManagerRegistry           $registry
     * @param ProductUnitLabelFormatter $formatter
     */
    public function __construct(ManagerRegistry $registry, ProductUnitLabelFormatter $formatter)
    {
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

        $choices = [];
        $units = $this->registry
            ->getManagerForClass('OroB2BProductBundle:ProductUnit')
            ->getRepository('OroB2BProductBundle:ProductUnit')
            ->findAll();
        foreach ($units as $unit) {
            $choices[$unit->getCode()] = $this->formatter->format($unit->getCode());
        }

        $builder->add(
            'unit',
            'choice',
            [
                'required' => true,
                'choices' => $choices,
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([]);
    }
}
