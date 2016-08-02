<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class QuickAddImportFromFileType extends AbstractType
{
    const NAME = 'orob2b_product_quick_add_import_from_file';
    const FILE_FIELD_NAME = 'file';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::FILE_FIELD_NAME,
                'file',
                [
                    'required' => true,
                    'label' => false,
                    'constraints' => [
                        new File(
                            [
                                'mimeTypes' => ['text/plain', 'application/zip'],
                                'mimeTypesMessage' => 'orob2b.product.frontend.quick_add.invalid_file_type'
                            ]
                        ),
                        new NotBlank(),
                    ]
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
