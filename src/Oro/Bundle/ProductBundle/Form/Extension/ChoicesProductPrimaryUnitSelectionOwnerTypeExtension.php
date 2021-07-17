<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Form\Type\ProductPrimaryUnitPrecisionType;
use Oro\Bundle\ProductBundle\Form\Type\Traits\ProductAwareTrait;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ChoicesProductPrimaryUnitSelectionOwnerTypeExtension extends AbstractTypeExtension
{
    use ProductAwareTrait;

    /**
     * @var ProductUnitFieldsSettingsInterface
     */
    private $productFormUnitFieldsSettings;

    /**
     * @var string
     */
    private $childName;

    public function __construct($childName, ProductUnitFieldsSettingsInterface $productFormUnitFieldsSettings)
    {
        $this->childName = $childName;
        $this->productFormUnitFieldsSettings = $productFormUnitFieldsSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'setAvailableUnits']);
    }

    public function setAvailableUnits(FormEvent $event)
    {
        $form = $event->getForm();
        $child = $form->get($this->childName);
        if (!$child) {
            throw new \InvalidArgumentException(
                sprintf('Unknown %s child in %s', $this->childName, $this->getExtendedType())
            );
        }
        $options = $child->getConfig()->getOptions();
        $product = $this->getProduct($child);

        $options['choices'] = $this->productFormUnitFieldsSettings->getAvailablePrimaryUnitChoices($product);
        $options['choices_updated'] = true;
        $options['choice_loader'] = null;

        $form->add($child->getName(), get_class($child->getConfig()->getType()->getInnerType()), $options);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductPrimaryUnitPrecisionType::class];
    }
}
