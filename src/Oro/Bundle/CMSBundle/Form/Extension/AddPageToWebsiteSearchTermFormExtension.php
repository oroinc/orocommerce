<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Type\PageSelectType;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds "cms_page" choice to redirect action type and "redirectCmsPage" field to {@see SearchTermType} form.
 */
class AddPageToWebsiteSearchTermFormExtension extends AbstractTypeExtension
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
            if ($value === 'category') {
                $redirectChoices['oro.websitesearchterm.searchterm.redirect_action_type.choices.cms_page.label'] =
                    'cms_page';
            }
        }

        $builder
            ->add(
                'redirectActionType',
                $redirectTypeFormConfig->getType()->getInnerType()::class,
                ['choices' => $redirectChoices] + $redirectTypeFormConfig->getOptions()
            )
            ->add(
                'redirectCmsPage',
                PageSelectType::class,
                [
                    'required' => true,
                    'create_enabled' => false,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('disable_fields_if', function (Options $options, $previousValue) {
                return (array)$previousValue + [
                        'redirectCmsPage' => 'data.actionType != "redirect" || data.redirectActionType != "cms_page"',
                    ];
            });
    }
}
