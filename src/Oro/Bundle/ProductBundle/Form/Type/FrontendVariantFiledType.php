<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;

class FrontendVariantFiledType extends AbstractType
{
    const NAME = 'oro_product_frontend_variant_field';

    /** @var CustomFieldProvider */
    protected $customFieldProvider;

    /** @var ProductVariantAvailabilityProvider */
    protected $productVariantAvailabilityProvider;

    /** @var string */
    protected $productClass;

    /**
     * @param CustomFieldProvider $customFieldProvider
     * @param ProductVariantAvailabilityProvider $productVariantAvailabilityProvider
     * @param string $productClass
     */
    public function __construct(
        CustomFieldProvider $customFieldProvider,
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        $productClass
    ) {
        $this->customFieldProvider = $customFieldProvider;
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->productClass = (string)$productClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();

        if ($data === null) {
            return;
        }

        $form = $event->getForm();

        /** @var Product $product */
        $product = $form->getConfig()->getOption('product');

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

        $fieldsToSearch = $data;// [];
//        foreach ($data as $name => $value) {
//            if ('' !== $value) {
//                $fieldsToSearch[$name] = $value;
//            }
//        }

        $class = ClassUtils::getClass($product);

        $variantFieldData = $this->customFieldProvider->getEntityCustomFields($class);

        $variantAvailability = $this->productVariantAvailabilityProvider
            ->getVariantFieldsWithAvailability($product, $fieldsToSearch);

        foreach ($product->getVariantFields() as $fieldName) {
            list($type, $fieldOptions) = $this->prepareFieldByType(
                $variantFieldData[$fieldName]['type'],
                $fieldName,
                $class,
                $variantAvailability[$fieldName]
            );
            $fieldOptions['label'] = $variantFieldData[$fieldName]['label'];

            $form->add($fieldName, $type, $fieldOptions);
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
//                $options['non_default_options'] = $options['disabled_values'];

                return [FrontendVariantEnumSelectType::NAME, $options];
            case 'boolean':
                $options = $this->getBooleanOptions();
                $options['choice_attr'] = $this->getBooleanDisabledValues($availability);

                return [FrontendVariantBooleanType::NAME, $options];
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
     * @param array $availability
     * @return \Closure
     */
    private function getBooleanDisabledValues(array $availability)
    {
        $availableVariants = array_filter($availability);

        return function ($val, $key, $index) use ($availableVariants) {
            $disabled = !array_key_exists($key, $availableVariants);

            return $disabled ? ['disabled' => 'disabled'] : [];
        };
    }
}
