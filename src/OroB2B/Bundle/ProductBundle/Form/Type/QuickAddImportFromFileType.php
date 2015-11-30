<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\FileToRowCollectionTransformer;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\QuickAddRowCollection;

class QuickAddImportFromFileType extends AbstractType
{
    const NAME = 'orob2b_product_quick_add_import_from_file';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                $builder->create(
                    QuickAddType::PRODUCTS_FIELD_NAME,
                    'file',
                    [
                        'required' => true,
                        'constraints' => [
                            new File(
                                [
                                    'mimeTypes' => [
                                        'text/plain',
                                        'text/csv',
                                        'application/octet-stream',
                                        'application/zip'
                                    ],
                                    'mimeTypesMessage' => 'This file type is not allowed.'
                                ]
                            ),
                            new NotBlank(),
                            new QuickAddRowCollection()
                        ]
                    ]
                )->addModelTransformer(new FileToRowCollectionTransformer())
            );

    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
