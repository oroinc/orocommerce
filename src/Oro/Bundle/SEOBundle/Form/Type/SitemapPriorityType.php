<?php

namespace Oro\Bundle\SEOBundle\Form\Type;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Form type for entering sitemap priority values.
 *
 * This form type provides a numeric input field for specifying the priority of URLs in the sitemap.
 * It extends the Symfony NumberType and enforces constraints to ensure values are between 0 and 1 (inclusive)
 * and are valid decimal numbers, conforming to the sitemap protocol specification.
 */
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
