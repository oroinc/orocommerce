<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Provides functionality to create localized slugs with redirect.
 */
class LocalizedSlugWithRedirectType extends AbstractType
{
    const NAME = 'oro_redirect_localized_slug_with_redirect';
    const SLUG_PROTOTYPES_FIELD_NAME = 'slugPrototypes';
    const CREATE_REDIRECT_FIELD_NAME = 'createRedirect';

    /**
     * @var ConfirmSlugChangeFormHelper
     */
    private $confirmSlugChangeFormHelper;

    public function __construct(ConfirmSlugChangeFormHelper $confirmSlugChangeFormHelper)
    {
        $this->confirmSlugChangeFormHelper = $confirmSlugChangeFormHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $constraints = [new UrlSafe(['allowSlashes' => $options['allow_slashes']])];
        if (!empty($options['required'])) {
            $constraints[] = new NotBlank();
        }

        $builder
            ->add(
                self::SLUG_PROTOTYPES_FIELD_NAME,
                LocalizedSlugType::class,
                [
                    'required' => !empty($options['required']),
                    'entry_options'  => ['constraints' => $constraints],
                    'source_field' => $options['source_field'],
                    'label' => false,
                    'slug_suggestion_enabled' => $options['slug_suggestion_enabled'],
                ]
            )
            ->add(
                self::CREATE_REDIRECT_FIELD_NAME,
                CheckboxType::class,
                [
                    'label' => 'oro.redirect.confirm_slug_change.checkbox_label',
                    'data' => true,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'create_redirect_enabled' => true,
            'slug_suggestion_enabled' => true,
            'data_class' => SlugPrototypesWithRedirect::class,
            'get_changed_slugs_url' => null,
            'tooltip' => 'oro.redirect.slug_prototypes.tooltip',
            'allow_slashes' => false,
        ]);
        $resolver->setRequired('source_field');

        $resolver->setAllowedTypes('get_changed_slugs_url', ['string', 'null']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->confirmSlugChangeFormHelper->addConfirmSlugChangeOptionsLocalized($view, $form, $options);

        if (!$options['get_changed_slugs_url']) {
            $view->vars['confirm_slug_change_component_options']['disabled'] = true;
        } else {
            $view->vars['confirm_slug_change_component_options']['changedSlugsUrl']
                = $options['get_changed_slugs_url'];
        }
    }
}
