<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Form\Extension\WebsiteSearchTerm;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentChoiceType;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds "product_collection" choice to modify action type and "productCollectionSegment" field
 * to {@see SearchTermType} form.
 */
class AddProductCollectionToWebsiteSearchTermFormExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [SearchTermType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $modifyTypeForm = $builder->get('modifyActionType');
        $modifyTypeFormConfig = $modifyTypeForm->getFormConfig();
        $modifyChoices = $modifyTypeFormConfig->getOption('choices');
        $modifyChoices['oro.websitesearchterm.searchterm.modify_action_type.choices.product_collection.label'] =
            'product_collection';

        $builder
            ->add(
                'modifyActionType',
                $modifyTypeFormConfig->getType()->getInnerType()::class,
                ['choices' => $modifyChoices] + $modifyTypeFormConfig->getOptions()
            )
            ->add(
                'productCollectionSegment',
                SegmentChoiceType::class,
                [
                    'required' => true,
                    'entityClass' => Product::class,
                    'entityChoices' => true,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('disable_fields_if', function (Options $options, $previousValue) {
                return (array)$previousValue + [
                        'productCollectionSegment' => 'data.actionType != "modify" || '
                            . 'data.modifyActionType != "product_collection"',
                    ];
            });
    }
}
