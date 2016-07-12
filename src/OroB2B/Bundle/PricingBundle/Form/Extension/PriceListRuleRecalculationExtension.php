<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\PricingBundle\Form\Type\PriceListType;
use OroB2B\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use OroB2B\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;

/**
 * Temporary recalculate rules after price list save.
 * TODO: Remove or refactor after BB-3273
 */
class PriceListRuleRecalculationExtension extends AbstractTypeExtension
{
    /**
     * @var PriceListProductAssignmentBuilder
     */
    protected $assignmentBuilder;

    /**
     * @var ProductPriceBuilder
     */
    protected $priceBuilder;

    /**
     * @param PriceListProductAssignmentBuilder $assignmentBuilder
     * @param ProductPriceBuilder $priceBuilder
     */
    public function __construct(
        PriceListProductAssignmentBuilder $assignmentBuilder,
        ProductPriceBuilder $priceBuilder
    ) {
        $this->assignmentBuilder = $assignmentBuilder;
        $this->priceBuilder = $priceBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                if ($event->getForm()->isValid()) {
                    $priceList = $event->getData();
                    $this->assignmentBuilder->buildByPriceList($priceList);
                    $this->priceBuilder->buildByPriceList($priceList);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return PriceListType::NAME;
    }
}
