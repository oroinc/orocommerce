<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Form\Extension\WebsiteSearchTerm;

use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds "product" choice to redirect action type and "redirectProduct" field to {@see SearchTermType} form.
 */
class AddProductToWebsiteSearchTermFormExtension extends AbstractTypeExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [SearchTermType::class];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $redirectTypeForm = $builder->get('redirectActionType');
        $redirectTypeFormConfig = $redirectTypeForm->getFormConfig();
        $redirectChoices = [];
        foreach ($redirectTypeFormConfig->getOption('choices') as $label => $value) {
            $redirectChoices[$label] = $value;
            if ($value === 'content_node') {
                $redirectChoices['oro.websitesearchterm.searchterm.redirect_action_type.choices.product.label'] =
                    'product';
            }
        }

        $builder
            ->add(
                'redirectActionType',
                $redirectTypeFormConfig->getType()->getInnerType()::class,
                ['choices' => $redirectChoices] + $redirectTypeFormConfig->getOptions()
            )
            ->add(
                'redirectProduct',
                ProductSelectType::class,
                [
                    'required' => true,
                    // Enables also configurable products.
                    'autocomplete_alias' => 'oro_all_product_visibility_limited',
                    'grid_name' => 'all-products-select-grid',
                    'create_enabled' => false,
                ]
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('disable_fields_if', function (Options $options, $previousValue) {
                return (array)$previousValue + [
                        'redirectProduct' => 'data.actionType != "redirect" || data.redirectActionType != "product"',
                    ];
            });
    }
}
