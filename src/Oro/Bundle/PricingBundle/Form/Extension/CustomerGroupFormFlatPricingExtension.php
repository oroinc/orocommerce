<?php

namespace Oro\Bundle\PricingBundle\Form\Extension;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\EventListener\CustomerGroupFlatPricingRelationFormListener;
use Oro\Bundle\PricingBundle\Form\Type\PriceListRelationType;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

/**
 * Adds price list by website relation to Customer Group entity.
 */
class CustomerGroupFormFlatPricingExtension extends AbstractTypeExtension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var CustomerGroupFlatPricingRelationFormListener
     */
    private $listener;

    public function __construct(
        CustomerGroupFlatPricingRelationFormListener $listener
    ) {
        $this->listener = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [CustomerGroupType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $builder->add(
            'priceListsByWebsites',
            WebsiteScopedDataType::class,
            [
                'type' => PriceListRelationType::class,
                'label' => false,
                'required' => false,
                'mapped' => false,
                'data' => [],
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this->listener, 'onPostSetData']);
    }
}
