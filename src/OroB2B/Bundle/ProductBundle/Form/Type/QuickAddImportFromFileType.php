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
                    'constraints' => [
                        new File(
                            [
                                'mimeTypes' => [
                                    'text/plain',
                                    'text/csv',
                                    'application/vnd.oasis.opendocument.spreadsheet',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                ],
                                'mimeTypesMessage' => 'This file type is not allowed.'
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
        return self::NAME;
    }
}
