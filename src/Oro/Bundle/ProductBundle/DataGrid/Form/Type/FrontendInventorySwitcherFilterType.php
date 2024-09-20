<?php

namespace Oro\Bundle\ProductBundle\DataGrid\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The form type which can be used as an inventory switcher filter for datagrids with search datasource.
 */
class FrontendInventorySwitcherFilterType extends AbstractType
{
    public const TYPE_ENABLED = 1;

    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [
            $this->translator->trans('oro.product.frontend.product_inventory_filter.form.enabled.label') =>
                self::TYPE_ENABLED,
        ];

        $resolver->setDefaults(
            [
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => $choices,
                    'multiple' => true,
                ],
                'operator_choices' => $choices,
                'populate_default' => false,
                'default_value' => null,
                'null_value' => null,
                'class' => null,
                'enum_code' => Product::INVENTORY_STATUS_ENUM_CODE,
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (isset($options['populate_default'])) {
            $view->vars['populate_default'] = $options['populate_default'];
            $view->vars['default_value']    = $options['default_value'];
        }
        if (!empty($options['null_value'])) {
            $view->vars['null_value'] = $options['null_value'];
        }
        if (!empty($options['enum_code'])) {
            $view->vars['enum_code'] = $options['enum_code'];
        }
        if (!empty($options['class'])) {
            $view->vars['class'] = $options['class'];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return FilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_product_type_inventory_switcher_filter';
    }
}
