<?php

declare(strict_types=1);

namespace Oro\Bundle\CatalogBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds "category" choice to redirect action type and "redirectCategory" field to {@see SearchTermType} form.
 */
class AddCategoryToWebsiteSearchTermFormExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [SearchTermType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $redirectTypeForm = $builder->get('redirectActionType');
        $redirectTypeFormConfig = $redirectTypeForm->getFormConfig();
        $redirectChoices = [];
        foreach ($redirectTypeFormConfig->getOption('choices') as $label => $value) {
            $redirectChoices[$label] = $value;
            if ($value === 'product') {
                $redirectChoices['oro.websitesearchterm.searchterm.redirect_action_type.choices.category.label'] =
                    'category';
            }
        }

        $builder
            ->add(
                'redirectActionType',
                $redirectTypeFormConfig->getType()->getInnerType()::class,
                ['choices' => $redirectChoices] + $redirectTypeFormConfig->getOptions()
            )
            ->add(
                'redirectCategory',
                CategoryTreeType::class,
                [
                    'required' => true,
                    'error_bubbling' => false,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('disable_fields_if', function (Options $options, $previousValue) {
                return (array)$previousValue + [
                        'redirectCategory' => 'data.actionType != "redirect" || data.redirectActionType != "category"',
                    ];
            });
    }
}
