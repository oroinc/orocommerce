<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroB2B\Bundle\CustomerBundle\Form\DataTransformer\AddressTypeDefaultTransformer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CustomerTypedAddressWithDefaultType extends AbstractType
{
    const NAME = 'orob2b_customer_typed_address_with_default';

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['em'] === null) {
            $em = $this->registry->getManagerForClass($options['class']);
        } else {
            $em = $this->registry->getManager($options['em']);
        }

        $repository = $em->getRepository($options['class']);
        $entities   = $repository->findAll();

        $classMetadata   = $em->getClassMetadata($options['class']);
        $identifierField = $classMetadata->getSingleIdentifierFieldName();

        $choiceLabels = [];

        /** @var AddressType $entity */
        foreach ($entities as $entity) {
            $pkValue = $classMetadata->getReflectionProperty($identifierField)->getValue($entity);

            if ($options['property']) {
                $value = $classMetadata->getReflectionProperty($options['property'])->getValue($entity);
            } else {
                $value = (string)$entity;
            }

            $choiceLabels[$pkValue] = 'Default ' . $value;
        }

        $builder->add('default', 'choice', [
            'choices'  => $choiceLabels,
            'multiple' => true,
            'expanded' => true,
            'label'    => false,
        ])
        ->addViewTransformer(new AddressTypeDefaultTransformer($em));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'em'       => null,
            'property' => null
        ]);

        $resolver->setRequired([
            'class'
        ]);
    }

    /**
     * @param ManagerRegistry $registry
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
