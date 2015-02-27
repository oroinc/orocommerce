<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\AttributeTransformer;
use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\AttributeDisabledFieldsTransformer;
use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeRegistry;
use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\AttributeBundle\AttributeType\OptionAttributeTypeInterface;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedPropertyType;
use OroB2B\Bundle\FallbackBundle\Form\Type\WebsitePropertyType;

class UpdateAttributeType extends AbstractType
{
    const NAME = 'orob2b_attribute_update';

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var AttributeTypeRegistry
     */
    protected $typeRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param AttributeTypeRegistry $typeRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry, AttributeTypeRegistry $typeRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        $this->typeRegistry = $typeRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $attribute = $options['data'];
        if (!$attribute instanceof Attribute) {
            throw new UnexpectedTypeException($attribute, 'Attribute');
        }

        $attributeType = $this->typeRegistry->getTypeByName($attribute->getType());
        if (!$attributeType) {
            throw new LogicException(sprintf('Attribute type "%s" not found', $attribute->getType()));
        }

        $this->addMainFields($builder);
        $this->addValidationFields($builder, $attributeType);
        if ($attributeType instanceof OptionAttributeTypeInterface) {
            $this->addDefaultOptionsField($builder, $attribute, $attributeType);
        } else {
            $this->addDefaultValueField($builder, $attribute, $attributeType);
        }
        $this->addPropertyFields($builder, $attributeType);
        $builder->addViewTransformer(new AttributeTransformer($this->managerRegistry, $this->typeRegistry, $attribute));

        $this->addDisabledFields($builder);
        $builder->addViewTransformer(new AttributeDisabledFieldsTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['data']);
        $resolver->setDefaults(['data_class' => null, 'validation_groups' => ['Update']]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addMainFields(FormBuilderInterface $builder)
    {
        $builder
            ->add('code', 'hidden')
            ->add('type', 'hidden')
            ->add('localized', 'checkbox', ['label' => 'orob2b.attribute.localized.label', 'required' => false])
            ->add(
                'sharingType',
                SharingTypeType::NAME,
                [
                    'label' => 'orob2b.attribute.sharing_type.label',
                    'required' => false,
                    'constraints' => [new NotBlank()],
                    'validation_groups' => ['Default']
                ]
            )
            ->add(
                'label',
                LocalizedPropertyType::NAME,
                [
                    'label' => 'orob2b.attribute.labels.label',
                    'type' => 'text',
                    'options' => ['constraints' => [new NotBlank()], 'validation_groups' => ['Default']]
                ]
            );
    }

    /**
     * @param FormBuilderInterface $builder
     * @param AttributeTypeInterface $attributeType
     */
    protected function addValidationFields(FormBuilderInterface $builder, AttributeTypeInterface $attributeType)
    {
        if ($attributeType->canBeRequired()) {
            $builder->add('required', 'checkbox', ['label' => 'orob2b.attribute.required.label', 'required' => false]);
        }

        if ($attributeType->canBeUnique()) {
            $builder->add('unique', 'checkbox', ['label' => 'orob2b.attribute.unique.label', 'required' => false]);
        }

        if ($attributeType->getOptionalConstraints()) {
            $builder->add(
                'validation',
                AttributeTypeConstraintType::NAME,
                [
                    'label' => 'orob2b.attribute.validation.label',
                    'required' => false,
                    'attribute_type' => $attributeType
                ]
            );
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param Attribute $attribute
     * @param AttributeTypeInterface $attributeType
     */
    protected function addDefaultValueField(
        FormBuilderInterface $builder,
        Attribute $attribute,
        AttributeTypeInterface $attributeType
    ) {
        $formParameters = $attributeType->getFormParameters($attribute);
        if (empty($formParameters['type'])) {
            throw new LogicException(sprintf('Form type is required for attribute type "%s"', $attribute->getType()));
        }

        $formType = $formParameters['type'];
        $formOptions = !empty($formParameters['options']) ? $formParameters['options'] : [];

        $requiredConstraints = $attributeType->getRequiredConstraints();
        if ($requiredConstraints) {
            $formOptions['constraints'] = $requiredConstraints;
            $formOptions['validation_groups'] = ['Default'];
        }

        if ($attribute->isLocalized()) {
            $builder->add(
                'defaultValue',
                LocalizedPropertyType::NAME,
                [
                    'label' => 'orob2b.attribute.default_values.label',
                    'required' => false,
                    'type' => $formType,
                    'options' => array_merge($formOptions, ['required' => false])
                ]
            );
        } else {
            $builder->add(
                'defaultValue',
                $formType,
                array_merge($formOptions, ['label' => 'orob2b.attribute.default_values.label', 'required' => false])
            );
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        $isLocalizedForm = $form->get('defaultValue')->getConfig()->getType()->getName()
            == LocalizedPropertyType::NAME;
        $isLocalizedValue = array_key_exists('defaultValue', $data)
            && $this->isLocalizedValue($data['defaultValue']);

        // normalize value
        $defaultValue = null;
        if (!$isLocalizedForm && $isLocalizedValue) {
            if (array_key_exists(LocalizedPropertyType::FIELD_DEFAULT, $data['defaultValue'])) {
                $defaultValue = $data['defaultValue'][LocalizedPropertyType::FIELD_DEFAULT];
            }
            $data['defaultValue'] = $defaultValue;
        } elseif ($isLocalizedForm && !$isLocalizedValue) {
            if (array_key_exists('defaultValue', $data)) {
                $defaultValue = $data['defaultValue'];
            }
            $data['defaultValue'] = [LocalizedPropertyType::FIELD_DEFAULT => $defaultValue];
        }

        $event->setData($data);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isLocalizedValue($value)
    {
        return is_array($value)
            && (array_key_exists(LocalizedPropertyType::FIELD_DEFAULT, $value)
            || array_key_exists(LocalizedPropertyType::FIELD_LOCALES, $value));
    }

    /**
     * @param FormBuilderInterface $builder
     * @param Attribute $attribute
     * @param OptionAttributeTypeInterface $attributeType
     */
    protected function addDefaultOptionsField(
        FormBuilderInterface $builder,
        Attribute $attribute,
        OptionAttributeTypeInterface $attributeType
    ) {
        $formParameters = $attributeType->getDefaultValueFormParameters($attribute);
        if (empty($formParameters['type'])) {
            throw new LogicException(sprintf('Form type is required for attribute type "%s"', $attribute->getType()));
        }

        $formType = $formParameters['type'];
        $formOptions = !empty($formParameters['options']) ? $formParameters['options'] : [];

        $builder->add(
            'defaultOptions',
            $formType,
            array_merge($formOptions, ['label' => 'orob2b.attribute.options.label', 'required' => false])
        );
    }

    /**
     * @param FormBuilderInterface $builder
     * @param AttributeTypeInterface $attributeType
     */
    protected function addPropertyFields(FormBuilderInterface $builder, AttributeTypeInterface $attributeType)
    {
        $builder->add(
            'onProductView',
            WebsitePropertyType::NAME,
            [
                'label' => 'orob2b.attribute.attributeproperty.fields.on_product_view',
                'required' => false,
                'type' => 'checkbox',
            ]
        )
        ->add(
            'inProductListing',
            WebsitePropertyType::NAME,
            [
                'label' => 'orob2b.attribute.attributeproperty.fields.in_product_listing',
                'required' => false,
                'type' => 'checkbox',
            ]
        )->add(
            'useInSorting',
            WebsitePropertyType::NAME,
            [
                'label' => 'orob2b.attribute.attributeproperty.fields.use_in_sorting',
                'required' => false,
                'type' => 'checkbox',
            ]
        )->add(
            'onAdvancedSearch',
            WebsitePropertyType::NAME,
            [
                'label' => 'orob2b.attribute.attributeproperty.fields.on_advanced_search',
                'required' => false,
                'type' => 'checkbox',
            ]
        )->add(
            'onProductComparison',
            WebsitePropertyType::NAME,
            [
                'label' => 'orob2b.attribute.attributeproperty.fields.on_product_comparison',
                'required' => false,
                'type' => 'checkbox',
            ]
        );

        if ($attributeType->isContainHtml()) {
            $builder->add(
                'containHtml',
                'checkbox',
                ['label' => 'orob2b.attribute.contain_html.label', 'required' => false]
            );
        }

        if ($attributeType->isUsedForSearch()) {
            $builder->add(
                'useForSearch',
                WebsitePropertyType::NAME,
                [
                    'label' => 'orob2b.attribute.attributeproperty.fields.use_for_search',
                    'required' => false,
                    'type' => 'checkbox',
                ]
            );
        }

        if ($attributeType->isUsedInFilters()) {
            $builder->add(
                'useInFilters',
                WebsitePropertyType::NAME,
                [
                    'label' => 'orob2b.attribute.attributeproperty.fields.use_in_filters',
                    'required' => false,
                    'type' => 'checkbox',
                ]
            );
        }
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addDisabledFields(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'codeDisabled',
                'text',
                ['label' => 'orob2b.attribute.code.label', 'disabled' => true]
            )
            ->add(
                'typeDisabled',
                AttributeTypeType::NAME,
                ['label' => 'orob2b.attribute.type.label', 'disabled' => true]
            );
    }
}
