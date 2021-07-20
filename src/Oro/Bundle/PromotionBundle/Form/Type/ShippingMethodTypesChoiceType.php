<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProviderInterface;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingMethodTypesChoiceType extends AbstractType
{
    const NAME = 'oro_promotion_shipping_methods';

    /**
     * @var AssetHelper
     */
    private $assetHelper;

    /**
     * @var ShippingMethodProviderInterface
     */
    private $provider;

    /**
     * @var ShippingMethodIconProviderInterface
     */
    private $iconProvider;

    public function __construct(
        ShippingMethodProviderInterface $provider,
        ShippingMethodIconProviderInterface $iconProvider,
        AssetHelper $assetHelper
    ) {
        $this->provider = $provider;
        $this->iconProvider = $iconProvider;
        $this->assetHelper = $assetHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
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

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'placeholder' => false,
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
        $choiceInfo = json_decode($choice, true);
        $iconUri = $this->iconProvider->getIcon($choiceInfo[ShippingDiscount::SHIPPING_METHOD]);

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
        $shippingTypeChoices = [];
        $shippingMethods = $this->provider->getShippingMethods();
        foreach ($shippingMethods as $shippingMethod) {
            $shippingTypes = $shippingMethod->getTypes();

            foreach ($shippingTypes as $shippingType) {
                $info = json_encode([
                    ShippingDiscount::SHIPPING_METHOD => $shippingMethod->getIdentifier(),
                    ShippingDiscount::SHIPPING_METHOD_TYPE => $shippingType->getIdentifier()
                ]);
                $shippingTypeChoices[$shippingType->getLabel()] = $info;
            }
        }

        return $shippingTypeChoices;
    }
}
