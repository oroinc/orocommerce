<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Provider\BrandStatusProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Brand status
 */
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

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => $this->brandStatusProvider->getAvailableBrandStatuses(),
            'preferred_choices' => [Brand::STATUS_DISABLED],
            'duplicate_preferred_choices' => false
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
