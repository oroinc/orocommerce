<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\CopyPasteToProductsTransformer;

class QuickAddCopyPasteType extends AbstractType
{
    const NAME = 'orob2b_product_quick_add_copy_paste';

    const PRODUCTS_FIELD_NAME = 'products';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                $builder->create(
                    self::PRODUCTS_FIELD_NAME,
                    'textarea',
                    [
                        'required' => false,
                        'error_bubbling' => false,
                        'constraints' => [new NotBlank()],
                        'label' => false,
                        'data' => "HSSUC, 1\nHSTUC, 2\nHCCM, 3\nSKU1, 10\nSKU2,20\nSKU3, 30\n"
                    ]
                )->addModelTransformer(new CopyPasteToProductsTransformer())
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
