<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Type\ContentBlockSelectType;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds "contentBlock" field to {@see SearchTermType} form.
 */
class AddContentBlockToWebsiteSearchTermFormExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [SearchTermType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'contentBlock',
                ContentBlockSelectType::class,
                [
                    'required' => false,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('disable_fields_if', function (Options $options, $previousValue) {
                return (array)$previousValue + [
                        'contentBlock' => 'data.actionType != "modify"',
                    ];
            });
    }
}
