<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Form type for rendering textarea with Regex constrain
 */
class QuickAddCopyPasteType extends AbstractType
{
    const NAME = 'oro_product_quick_add_copy_paste';
    const COPY_PASTE_FIELD_NAME = 'copyPaste';

    /**
     * The regex for matching lines, separated by space, comma or semicolon
     * that contains: item sku, quantity, unit name
     */
    const VALIDATION_REGEX = '/^(([^\s,;]+[\t,; ]\d{1,32}([.,]\d{1,32})?([\t,; ][^\s,;]+)?)?(\n|$))+$/';

    /**
     * The regex for extract item sku, quantity, unit name from one line
     */
    const ITEM_PARSE_REGEX = '/^([^\s,;]+)(?:[\t,; ](\d{1,32}(?:[.,]\d{1,32})?)(?:[\t,; ]([^\s,;]+))?)?$/';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
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
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
