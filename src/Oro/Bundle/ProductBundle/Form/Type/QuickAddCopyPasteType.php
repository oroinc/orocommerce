<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Validator\Constraints\QuickAddComponentProcessor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * The form type for rendering quick order textarea with Regex constrain.
 */
class QuickAddCopyPasteType extends AbstractType
{
    public const string COPY_PASTE_FIELD_NAME = 'copyPaste';
    public const string COMPONENT_FIELD_NAME = 'component';

    /**
     * The regex for matching lines, separated by space, comma or semicolon
     * that contains: item sku, quantity, unit name.
     */
    private const string VALIDATION_REGEX =
        '/^(([^\s,;]+[\t,; ]\d{1,32}([.,]\d{1,32})?([\t,; ][^\s,;]+)?)?(\r?\n|$))+$/';

    /**
     * The regex for extracting item sku, quantity, unit name from one line.
     */
    private const string ITEM_PARSE_REGEX =
        '/^(?<sku>([^\s,;]+))(?:[\t,; ]'
        . '(?<quantity>(\d{1,32}(?:[.,]\d{1,32})?))(?:[\t,; ]'
        . '(?<unit>([^\s,;]+)))?)?$/';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            self::COPY_PASTE_FIELD_NAME,
            TextareaType::class,
            [
                'constraints' => [
                    new Regex([
                        'pattern' => self::VALIDATION_REGEX,
                        'message' => 'oro.product.frontend.quick_add.invalid_format'
                    ]),
                ],
                'label' => false,
                'attr' => [
                    'placeholder' => 'oro.product.frontend.quick_add.copy_paste.placeholder',
                    'spellcheck' => 'false',
                    'data-item-parse-pattern' => self::ITEM_PARSE_REGEX,
                ],
            ]
        )
        ->add(
            self::COMPONENT_FIELD_NAME,
            HiddenType::class,
            [
                'required' => false,
                'constraints' => [
                    new QuickAddComponentProcessor(),
                ],
            ]
        );
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_product_quick_add_copy_paste';
    }
}
