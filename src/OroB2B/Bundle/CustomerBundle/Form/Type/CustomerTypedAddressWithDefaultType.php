<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerAddressToAddressType;
use OroB2B\Bundle\CustomerBundle\Form\DataTransformer\AddressTypeDefaultTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CustomerTypedAddressWithDefaultType extends AbstractType
{
    const NAME = 'orob2b_customer_typed_address_with_default';
    /**
     * @var ManagerRegistry
     */
    protected $registry;

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

    public function setRegistry($registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'form';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
