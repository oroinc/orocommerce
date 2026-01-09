<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\TypeHandler;

use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantTypeHandlerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormFactory;

/**
 * Handles form creation for boolean-type product variant fields.
 *
 * This handler creates choice forms for boolean variant fields, converting boolean values into
 * user-friendly choice options for variant selection in the storefront.
 */
class BooleanTypeHandler implements ProductVariantTypeHandlerInterface
{
    public const TYPE = 'boolean';

    /** @var FormFactory */
    protected $formFactory;

    public function __construct(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    #[\Override]
    public function createForm($fieldName, array $availability, array $options = [])
    {
        $options = array_merge(
            $this->getOptions($availability),
            $options
        );

        return $this->formFactory->createNamed($fieldName, ChoiceType::class, null, $options);
    }

    /**
     * @return string
     */
    #[\Override]
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @param array $availability
     * @return array
     */
    private function getOptions(array $availability)
    {
        $availableVariants = array_filter($availability);

        $choiceAttrCallback = function ($val, $key, $index) use ($availableVariants) {
            $disabled = !array_key_exists((int)$val, $availableVariants);

            return $disabled ? ['disabled' => 'disabled'] : [];
        };

        return [
            'choices' => [
                'No' => false,
                'Yes' => true,
            ],
            'choice_attr' => $choiceAttrCallback,
            'auto_initialize' => false
        ];
    }
}
