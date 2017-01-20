<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\TypeHandler;

use Symfony\Component\Form\FormFactory;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantTypeHandlerInterface;

class BooleanTypeHandler implements ProductVariantTypeHandlerInterface
{
    const TYPE = 'boolean';

    /** @var FormFactory */
    protected $formFactory;

    /**
     * @param FormFactory $formFactory
     */
    public function __construct(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createForm($fieldName, array $availability, array $options = [])
    {
        $options = array_merge(
            $this->getOptions($availability),
            $options
        );

        return $this->formFactory->createNamed($fieldName, 'choice', null, $options);
    }

    /**
     * @return string
     */
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
            'choices_as_values' => true,
            'choice_attr' => $choiceAttrCallback,
            'auto_initialize' => false
        ];
    }
}
