<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProviderInterface;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingMethodSelectType extends AbstractType
{
    const NAME = 'oro_shipping_method_select';

    /**
     * @var AssetHelper
     */
    private $assetHelper;

    /**
     * @var ShippingMethodChoicesProviderInterface
     */
    private $provider;

    /**
     * @var ShippingMethodIconProviderInterface
     */
    private $iconProvider;

    public function __construct(
        ShippingMethodChoicesProviderInterface $provider,
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'placeholder' => 'oro.shipping.sections.shippingrule_configurations.placeholder.label',
            'choices' => $this->getChoices(),
            'choice_attr' => function ($choice) {
                return $this->getChoiceAttributes($choice);
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
    public function getParent()
    {
        return OroChoiceType::class;
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * @param string $choice Shipping method identifier
     *
     * @return array
     */
    private function getChoiceAttributes($choice)
    {
        $attributes = [];
        $iconUri = $this->iconProvider->getIcon($choice);

        if ($iconUri) {
            $attributes = ['data-icon' => $this->assetHelper->getUrl($iconUri)];
        }

        return $attributes;
    }

    /**
     * @return array
     */
    private function getChoices()
    {
        return $this->provider->getMethods();
    }
}
