<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\TextareaToRowCollectionTransformer;

class QuickAddCopyPasteType extends AbstractType
{
    const NAME = 'orob2b_product_quick_add_copy_paste';

    const PRODUCTS_FIELD_NAME = 'products';

    const FORMAT_REGEX = '/[^\s]+[\t\,][^\s]+/';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                $builder->create(
                    'collection',
                    'textarea',
                    [
                        'constraints' => [
                                new NotBlank(),
                                new Regex(['message' => 'Invalid format', 'pattern' => self::FORMAT_REGEX])
                        ],
                        'label' => false,
                        'data' => "HSSUC, 1\nHSTUC, 2\nHCCM, 3\nSKU1, 10\nSKU2,20\nSKU3, 30\n",
                    ]
                )->addModelTransformer(new TextareaToRowCollectionTransformer())
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\ProductBundle\Model\QuickAddCopyPaste'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
