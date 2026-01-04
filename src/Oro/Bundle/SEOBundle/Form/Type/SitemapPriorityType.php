<?php

namespace Oro\Bundle\SEOBundle\Form\Type;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class SitemapPriorityType extends AbstractType
{
    public const NAME = 'oro_sitemap_priority';

    #[\Override]
    public function getParent(): ?string
    {
        return NumberType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'constraints' => [
                new Range(['min' => 0, 'max' => 1]),
                new Decimal(),
            ],
        ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
