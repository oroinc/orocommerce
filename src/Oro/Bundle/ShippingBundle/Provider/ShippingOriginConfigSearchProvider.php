<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Provider\SearchProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provide information about shipping origin for config search.
 */
class ShippingOriginConfigSearchProvider implements SearchProviderInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(TranslatorInterface $translator, ConfigManager $configManager)
    {
        $this->translator = $translator;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        return $name === 'oro_shipping.shipping_origin';
    }

    /**
     * {@inheritdoc}
     */
    public function getData($name)
    {
        return array_merge(
            [
                $this->translator->trans('oro.shipping.shipping_origin.country.label'),
                $this->translator->trans('oro.shipping.shipping_origin.region.label'),
                $this->translator->trans('oro.shipping.shipping_origin.postal_code.label'),
                $this->translator->trans('oro.shipping.shipping_origin.city.label'),
                $this->translator->trans('oro.shipping.shipping_origin.street.label'),
                $this->translator->trans('oro.shipping.shipping_origin.street2.label'),
                $this->translator->trans('oro.shipping.shipping_origin.region_text.label')
            ],
            array_values($this->configManager->get($name))
        );
    }
}
