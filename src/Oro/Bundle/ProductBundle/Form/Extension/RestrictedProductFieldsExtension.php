<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Restricts changing collections of 'image' and 'unitPrecisions' fields if permissions are limited.
 */
class RestrictedProductFieldsExtension extends AbstractTypeExtension
{
    public function __construct(private FieldAclHelper $fieldAclHelper)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$this->fieldAclHelper->isFieldAclEnabled(Product::class)) {
            return;
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $product = $event->getData();

        $this->restrictAdditionalUnitPrecisionsField($product, $event->getForm());
        $this->restrictImageField($product, $event->getForm());
    }

    private function restrictAdditionalUnitPrecisionsField(object $product, FormInterface $form): void
    {
        $isUnitGranted = $this->fieldAclHelper->isFieldModificationGranted($product, 'unitPrecisions');
        FormUtils::replaceFieldOptionsRecursive(
            $form,
            'additionalUnitPrecisions',
            ['allow_add' => $isUnitGranted, 'allow_delete' => $isUnitGranted, 'check_field_name' => 'unitPrecisions']
        );
    }

    private function restrictImageField(object $product, FormInterface $form): void
    {
        $isImageGranted = $this->fieldAclHelper->isFieldModificationGranted($product, 'images');
        # Responsible for the collection item itself.
        $collectionOptions = array_fill_keys(['allow_add', 'allow_delete'], $isImageGranted);
        # Responsible for the specific image itself.
        $entryOptions = array_fill_keys(['allowDelete', 'allowUpdate'], $isImageGranted);
        $options = array_merge($collectionOptions, ['entry_options' => $entryOptions]);

        FormUtils::replaceFieldOptionsRecursive($form, 'images', $options);
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }
}
