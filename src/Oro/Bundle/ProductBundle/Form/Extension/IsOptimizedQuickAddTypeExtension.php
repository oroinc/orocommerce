<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Component added back for theme layout BC from version 5.0
 * Brings back the is_optimized flag for the quick add forms, without the actual functionality.
 * The config option 'enable_quick_order_form_optimized' is not restored, and the option is forced to TRUE
 */
class IsOptimizedQuickAddTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [
            QuickAddCopyPasteType::class,
            QuickAddImportFromFileType::class,
            QuickAddType::class,
        ];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['is_optimized'] = $options['is_optimized'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_optimized' => true,
        ]);
    }
}
