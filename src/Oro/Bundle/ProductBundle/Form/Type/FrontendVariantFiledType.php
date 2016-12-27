<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;

class FrontendVariantFiledType extends AbstractType
{
    const NAME = 'oro_product_frontend_variant_field';

    /** @var CustomFieldProvider */
    protected $customFieldProvider;

    /** @var string */
    protected $productClass;

    /**
     * @param CustomFieldProvider $customFieldProvider
     * @param string $productClass
     */
    public function __construct(CustomFieldProvider $customFieldProvider, $productClass)
    {
        $this->customFieldProvider = $customFieldProvider;
        $this->productClass = (string)$productClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Product $product */
        $product = $options['product'];

        if (!is_a($product, $this->productClass)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Instance of class "%s" was expected, but "%s" given',
                    $this->productClass,
                    is_object($product) ? get_class($product) : gettype($product)
                )
            );
        }

        if (!$product->isConfigurable() || count($product->getVariantFields()) === 0) {
            return;
        }

        $class = ClassUtils::getClass($product);

        $variantFieldData = $this->customFieldProvider->getEntityCustomFields($class);

        // FIXME: test workaround
        $variantAvailability = [];
        foreach ($product->getVariantFields() as $fieldName) {
            $variantAvailability[$fieldName] = [
                '1' => true,
                '2' => false,
                '3' => true,
            ];
        }
        // FIXME: END

        foreach ($product->getVariantFields() as $fieldName) {
            list($type, $fieldOptions) = $this->prepareFieldByType(
                $variantFieldData[$fieldName]['type'],
                $fieldName,
                $class,
                $variantAvailability[$fieldName]
            );
            $fieldOptions['label'] = $variantFieldData[$fieldName]['label'];

            $builder->add($fieldName, $type, $fieldOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'product',
        ]);
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

    /**
     * @param string $type
     * @param string $fieldName
     * @param string $class
     * @param array $availability
     * @return array
     */
    private function prepareFieldByType($type, $fieldName, $class, array $availability)
    {
        switch ($type) {
            case 'enum':
                $options = $this->getEnumOptions($class, $fieldName);
                $options['disabled_values'] = $this->getEnumDisabledValues($availability);

                return [FrontendVariantEnumSelectType::NAME, $options];
            case 'boolean':
                $options = $this->getBooleanOptions();
                $options = $this->getBooleanDisabledValues($options, $availability);

                return ['choice', $options];
            default:
                throw new \LogicException(
                    sprintf(
                        'Incorrect type. Expected "%s", but "%s" given',
                        implode('" or "', ['boolean', 'enum']),
                        $type
                    )
                );
        }
    }

    /**
     * @param string $class
     * @param string $fieldName
     * @return array
     */
    private function getEnumOptions($class, $fieldName)
    {
        return [
            'enum_code' => ExtendHelper::generateEnumCode($class, $fieldName),
            'configs' => ['allowClear' => false],
            // Next two lines required for selecting first element
            'required' => true,
            'placeholder' => false,
        ];
    }

    /**
     * @return array
     */
    private function getBooleanOptions()
    {
        return [
            'choices' => ['No', 'Yes'],
            // Next two lines required for selecting first element
            'required' => true,
            'placeholder' => false,
        ];
    }

    /**
     * Returns name of fields which will be disabled
     *
     * @param array $availability
     * @return array
     */
    private function getEnumDisabledValues(array $availability)
    {
        // TODO: Possible we need to disable ALL variants which not present in $notAvailableVariants
        $notAvailableVariants = array_filter($availability, function ($item) {
            return $item === false;
        });

        return array_keys($notAvailableVariants);
    }

    /**
     * Returns name of fields which will be disabled
     *
     * @param array $options
     * @param array $availability
     * @return array
     */
    private function getBooleanDisabledValues(array $options, array $availability)
    {
        $availableVariants = array_filter($availability);

        $options['choice_attr'] = function ($val, $key, $index) use ($availableVariants) {
            $disabled = !array_key_exists($key, $availableVariants);

            return $disabled ? ['disabled' => 'disabled'] : [];
        };

        return $options;
    }
}
