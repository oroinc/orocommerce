<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProvider;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProviderInterface;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select a shipping method.
 */
class ShippingMethodSelectType extends AbstractType
{
    private AssetHelper $assetHelper;
    private ShippingMethodChoicesProvider $provider;
    private ShippingMethodIconProviderInterface $iconProvider;

    public function __construct(
        ShippingMethodChoicesProvider $provider,
        ShippingMethodIconProviderInterface $iconProvider,
        AssetHelper $assetHelper
    ) {
        $this->provider = $provider;
        $this->iconProvider = $iconProvider;
        $this->assetHelper = $assetHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'placeholder' => 'oro.shipping.sections.shippingrule_configurations.placeholder.label',
            'choices' => $this->provider->getMethods(),
            'choice_attr' => function (string $shippingMethodIdentifier) {
                $attributes = [];
                $iconUri = $this->iconProvider->getIcon($shippingMethodIdentifier);
                if ($iconUri) {
                    $attributes = ['data-icon' => $this->assetHelper->getUrl($iconUri)];
                }

                return $attributes;
            },
            'configs' => [
                'showIcon' => true,
                'minimumResultsForSearch' => 1,
            ]
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return OroChoiceType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_shipping_method_select';
    }
}
