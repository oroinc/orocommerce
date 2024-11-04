<?php

namespace Oro\Bundle\CMSBundle\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantType;
use Oro\Bundle\CMSBundle\Validator\Constraints\TextContentVariantScope;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

/**
 * Adds additional validation for scopes on content variants.
 */
class TextContentVariantDefaultScopesExtensions extends AbstractTypeExtension
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function ($event) {
            $contentVariant = $event->getData();
            if ($contentVariant['default'] ?? false) {
                return;
            }

            $constraints = ['scope_constraints' => [new TextContentVariantScope()]];
            FormUtils::mergeFieldOptionsRecursive($event->getForm(), 'scopes', $constraints);
        });
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [TextContentVariantType::class];
    }
}
