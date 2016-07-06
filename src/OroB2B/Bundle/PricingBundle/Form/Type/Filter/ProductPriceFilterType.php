<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class ProductPriceFilterType extends AbstractType
{
    const NAME = 'orob2b_pricing_product_price_filter';

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
     * @param TranslatorInterface       $translator
     * @param ManagerRegistry           $registry
     * @param ProductUnitLabelFormatter $formatter
     */
    public function __construct(
        TranslatorInterface $translator,
        ManagerRegistry $registry,
        ProductUnitLabelFormatter $formatter
    ) {
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
        return NumberRangeFilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_type' => NumberRangeFilterType::DATA_DECIMAL
        ]);
    }

    /**
     * Get choices list for unit field.
     *
     * @return array
     */
    protected function getUnitChoices()
    {
        $unitCodes = $this->registry
            ->getManagerForClass('OroB2BProductBundle:ProductUnit')
            ->getRepository('OroB2BProductBundle:ProductUnit')
            ->getAllUnitCodes();

        $choices = [];
        foreach ($unitCodes as $unitCode) {
            $choices[$unitCode] = $this->formatter->format($unitCode);
        }

        return $choices;
    }
}
