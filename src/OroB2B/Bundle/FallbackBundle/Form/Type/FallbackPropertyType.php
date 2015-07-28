<?php

namespace OroB2B\Bundle\FallbackBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\FallbackBundle\Model\FallbackType;

class FallbackPropertyType extends AbstractType
{
    const NAME = 'orob2b_fallback_property';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

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
                'empty_value'        => false,
                'enabled_fallbacks'  => [],
                'existing_fallbacks' => [
                    FallbackType::SYSTEM        => 'orob2b.fallback.type.default',
                    FallbackType::PARENT_LOCALE => 'orob2b.fallback.type.parent_locale',
                ],
                'parent_locale' => null,
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
                        if (!in_array($fallback, $enabledFallbacks, true)) {
                            unset($choices[$fallback]);
                        }
                    }

                    if (array_key_exists(FallbackType::PARENT_LOCALE, $choices) && $options['parent_locale']) {
                        $choices[FallbackType::PARENT_LOCALE] = sprintf(
                            '%s (%s)',
                            $this->translator->trans($choices[FallbackType::PARENT_LOCALE]),
                            $options['parent_locale']
                        );
                    }

                    return $choices;
                }
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['parent_locale']) {
            $view->vars['attr']['data-parent-locale'] = $options['parent_locale'];
        }
    }
}
