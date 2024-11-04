<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProvider;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProviderInterface;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 *  Provides shipping method choices to create or edit a Promotion entity
 */
class ShippingMethodTypesChoiceType extends AbstractType
{
    private ShippingMethodChoicesProvider $provider;
    private ShippingMethodIconProviderInterface $iconProvider;
    private AssetHelper $assetHelper;

    public function __construct(
        ShippingMethodChoicesProvider $provider,
        ShippingMethodIconProviderInterface $iconProvider,
        AssetHelper $assetHelper
    ) {
        $this->provider = $provider;
        $this->iconProvider = $iconProvider;
        $this->assetHelper = $assetHelper;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($shippingArrayInfo) {
                return json_encode($shippingArrayInfo);
            },
            function ($shippingJsonInfo) {
                return json_decode($shippingJsonInfo, true);
            }
        ));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'placeholder' => false,
            'choices' => $this->provider->getMethodTypes(),
            'choice_attr' => function ($choice) {
                $attributes = [];
                $choiceInfo = json_decode($choice, true);
                $iconUri = $this->iconProvider->getIcon($choiceInfo[ShippingDiscount::SHIPPING_METHOD]);
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

    #[\Override]
    public function getParent(): ?string
    {
        return OroChoiceType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_promotion_shipping_methods';
    }
}
