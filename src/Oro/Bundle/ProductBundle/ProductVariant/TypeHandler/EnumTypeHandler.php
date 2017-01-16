<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\TypeHandler;

use Symfony\Component\Form\FormFactory;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantTypeHandlerInterface;

class EnumTypeHandler implements ProductVariantTypeHandlerInterface
{
    const TYPE = 'enum';

    /** @var FormFactory */
    protected $formFactory;

    /** @var string */
    protected $productClass;

    /**
     * @param FormFactory $formFactory
     * @param string $productClass
     */
    public function __construct(FormFactory $formFactory, $productClass)
    {
        $this->formFactory = $formFactory;
        $this->productClass = $productClass;
    }

    /**
     * {@inheritdoc}
     */
    public function createForm($fieldName, array $availability, array $options = [])
    {
        $options = array_merge($this->getOptions($fieldName, $availability), $options);
        $form = $this->formFactory->createNamed($fieldName, EnumSelectType::NAME, null, $options);

        return $form;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @param string $fieldName
     * @param array $availability
     * @return array
     */
    private function getOptions($fieldName, array $availability)
    {
        $notAvailableVariants = array_filter($availability, function ($item) {
            return $item === false;
        });

        $disabledValues = array_keys($notAvailableVariants);

        return [
            'enum_code' => ExtendHelper::generateEnumCode($this->productClass, $fieldName),
            'configs' => ['allowClear' => false],
            'disabled_values' => $disabledValues,
            'auto_initialize' => false
        ];
    }
}
