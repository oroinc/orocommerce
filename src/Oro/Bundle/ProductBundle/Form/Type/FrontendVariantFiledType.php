<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
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

    /**
     * @param CustomFieldProvider $customFieldProvider
     */
    public function __construct(CustomFieldProvider $customFieldProvider)
    {
        $this->customFieldProvider = $customFieldProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Product $product */
        $product = $options['product'];

        if ($product->getType() === Product::TYPE_CONFIGURABLE && !empty($product->getVariantFields())) {
            $class = ClassUtils::getClass($product);

            $variantFieldData = $this->customFieldProvider->getEntityCustomFields($class);
            foreach ($product->getVariantFields() as $fieldName) {
                list($type, $fieldOptions) = $this->prepareFieldByType(
                    $variantFieldData[$fieldName]['type'],
                    $fieldName,
                    $class
                );
                $fieldOptions['label'] = $variantFieldData[$fieldName]['label'];

                $builder->add($fieldName, $type, $fieldOptions);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'product'
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
     * @return array
     */
    private function prepareFieldByType($type, $fieldName, $class)
    {
        switch ($type) {
            case 'enum':
                $options = $this->getEnumOptions($class, $fieldName);
                return [EnumSelectType::NAME, $options];
            case 'boolean':
                $options['choices'] = ['No', 'Yes'];
                return ['choice', $options];
            default:
                throw new \LogicException('Type can be "boolean" or "enum".');
        }
    }

    /**
     * @param string $class
     * @param string $fieldName
     * @return array
     */
    private function getEnumOptions($class, $fieldName)
    {
        return ['enum_code' => ExtendHelper::generateEnumCode($class, $fieldName)];
    }
}
