<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Type;

use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AttributeTypeConstraintType extends AbstractType
{
    const NAME = 'orob2b_attribute_type_constraint';

    /**
     * @var AttributeTypeInterface
     */
    protected $attributeType;

    /**
     * @var AttributeTypeRegistry
     */
    protected $attributeTypeRegistry;

    /**
     * @param AttributeTypeRegistry $registry
     */
    public function __construct(AttributeTypeRegistry $registry)
    {
        $this->attributeTypeRegistry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['attribute_type']);
        $resolver->setDefaults(['empty_value' => 'orob2b.attribute.form.attribute_type_constraint.none']);
        $resolver->setNormalizers(
            [
                'choices' => function (Options $options, $value) {
                    if (!empty($value)) {
                        return $value;
                    }

                    $choices = [];

                    if ($options['attribute_type'] instanceof AttributeTypeInterface) {
                        $constraints = $options['attribute_type']->getOptionalConstraints();
                    } elseif (is_string($options['attribute_type'])) {
                        $attributeType = $this->attributeTypeRegistry->getTypeByName($options['attribute_type']);
                        if (!empty($attributeType)) {
                            $constraints = $attributeType->getOptionalConstraints();
                        } else {
                            throw new \LogicException(
                                sprintf(
                                    'Attribute type name "%s" is not exist in attribute type registry.',
                                    $options['attribute_type']
                                )
                            );
                        }
                    } else {
                        throw new UnexpectedTypeException(
                            $options['attribute_type'],
                            'OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface or string'
                        );
                    }

                    foreach ($constraints as $choice) {
                        $choices[$choice->getAlias()] = 'orob2b.attribute.form.attribute_type_constraint.'
                            . $choice->getAlias();
                    }

                    return $choices;
                }
            ]
        );
    }
}
