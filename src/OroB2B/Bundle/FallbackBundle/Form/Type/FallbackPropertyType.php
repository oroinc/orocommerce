<?php

namespace OroB2B\Bundle\FallbackBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

use OroB2B\Bundle\FallbackBundle\Model\FallbackType;

class FallbackPropertyType extends AbstractType
{
    const NAME = 'orob2b_fallback_property';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'required'           => false,
                'empty_value'        => 'orob2b.fallback.type.empty',
                'enabled_fallbacks'  => [],
                'existing_fallbacks' => [
                    FallbackType::SYSTEM        => 'orob2b.fallback.type.default',
                    FallbackType::PARENT_LOCALE => 'orob2b.fallback.type.parent_locale',
                ],
            ]
        );

        $resolver->setNormalizers(
            [
                'choices' => function (Options $options, $value) {
                    if (!empty($value)) {
                        return $value;
                    }

                    // system fallback is always enabled
                    $enabledFallbacks = array_merge([FallbackType::SYSTEM], $options['enabled_fallbacks']);

                    $choices = $options['existing_fallbacks'];
                    foreach (array_keys($choices) as $fallback) {
                        if (!in_array($fallback, $enabledFallbacks)) {
                            unset($choices[$fallback]);
                        }
                    }

                    return $choices;
                }
            ]
        );
    }
}
