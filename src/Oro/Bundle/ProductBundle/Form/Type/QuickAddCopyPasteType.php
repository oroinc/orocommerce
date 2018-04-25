<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;

class QuickAddCopyPasteType extends AbstractType
{
    const NAME = 'oro_product_quick_add_copy_paste';
    const COPY_PASTE_FIELD_NAME = 'copyPaste';

    /**
     * The regex for matching lines, separated by space, comma or semicolon
     * that contains: item sku, quantity, unit name
     */
    const FORMAT_REGEX =
        '/^(?:\n|[-_a-zA-Z0-9]{1,255}[\t\,\; ]\d{1,234}(?:\.?\d{1,20})?(?:[\t\,\; ][a-zA-Z]{1,255})?(\n|\b))+$/';

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
                        'pattern' => self::FORMAT_REGEX,
                        'message' => 'oro.product.frontend.quick_add.invalid_format'
                    ]),
                ],
                'label' => false,
                'attr' => [
                    'placeholder' => 'oro.product.frontend.quick_add.copy_paste.placeholder',
                    'spellcheck' => 'false',
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
