<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Provider\BrandStatusProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BrandStatusType extends AbstractType
{
    const NAME = 'oro_brand_status';

    /**
     * @var  BrandStatusProvider $brandStatuses
     */
    protected $brandStatusProvider;

    public function __construct(BrandStatusProvider $brandStatusProvider)
    {
        $this->brandStatusProvider = $brandStatusProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => $this->brandStatusProvider->getAvailableBrandStatuses(),
            'preferred_choices' => Brand::STATUS_DISABLED
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
