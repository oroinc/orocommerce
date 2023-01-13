<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form type for quick add form import file.
 */
class QuickAddImportFromFileType extends AbstractType
{
    const NAME = 'oro_product_quick_add_import_from_file';
    const FILE_FIELD_NAME = 'file';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::FILE_FIELD_NAME,
                FileType::class,
                [
                    'required' => true,
                    'label' => false,
                    'constraints' => [
                        new NotBlank(
                            ['message' => 'oro.product.frontend.quick_add.validation.empty_file']
                        ),
                        new File(
                            [
                                'mimeTypes' => [
                                    'text/csv',
                                    'text/plain',
                                    'application/zip',
                                    'application/vnd.oasis.opendocument.spreadsheet',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    // xlsx
                                    'application/octet-stream'
                                ],
                            ]
                        ),
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
