<?php

namespace Oro\Bundle\PricingBundle\Form\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class PriceListFormExtension extends AbstractTypeExtension
{
    const MERGE_ALLOWED_FIELD = 'mergeAllowed';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return PriceListSelectWithPriorityType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->configManager->get('oro_pricing.price_strategy') === MergePricesCombiningStrategy::NAME) {
            $builder->add(
                self::MERGE_ALLOWED_FIELD,
                'checkbox',
                [
                    'label' => 'oro.pricing.pricelist.merge_allowed.label'
                ]
            );
        }
    }
}
