<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\Repository\AttributeOptionRepository;

class SelectAttributeTypeType extends AbstractType
{
    const NAME = 'orob2b_attribute_select_type';

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired([
            'attribute'
        ]);

        $resolver->setDefaults([
            'class' => $this->entityClass,
            'property' => 'value',
        ]);

        $resolver->setNormalizers([
            'query_builder' => function (Options $options) {
                $attribute = $options['attribute'];
                if (!$attribute instanceof Attribute) {
                    throw new UnexpectedTypeException($attribute, 'Attribute');
                }

                return function (AttributeOptionRepository $repository) use ($attribute) {
                    return $repository->createAttributeOptionsQueryBuilder($attribute);
                };
            }
        ]);
    }
}
