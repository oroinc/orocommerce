<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountGroupType extends AbstractType
{
    const NAME = 'oro_account_group_type';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var string
     */
    protected $accountClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param string $accountClass
     */
    public function setAccountClass($accountClass)
    {
        $this->accountClass = $accountClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                'text',
                [
                    'label' => 'oro.account.accountgroup.name.label',
                    'required' => true
                ]
            )
            ->add(
                'appendAccounts',
                'oro_entity_identifier',
                [
                    'class'    => $this->accountClass,
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true
                ]
            )
            ->add(
                'removeAccounts',
                'oro_entity_identifier',
                [
                    'class'    => $this->accountClass,
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => $this->dataClass]);
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
}
