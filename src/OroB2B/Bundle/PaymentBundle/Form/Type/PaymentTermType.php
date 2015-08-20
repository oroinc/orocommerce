<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

class PaymentTermType extends AbstractType
{
    const NAME = 'orob2b_payment_term';

    /** @var string */
    private $dataClass;

    /**
     * @var string
     */
    protected $accountClass;

    /**
     * @var string
     */
    protected $accountGroupClass;

    /**
     * @param string $dataClass
     */
    public function __construct($dataClass)
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
     * @param string $accountGroupClass
     */
    public function setAccountGroupClass($accountGroupClass)
    {
        $this->accountGroupClass = $accountGroupClass;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', 'text', ['required' => true, 'label' => 'orob2b.payment.paymentterm.label.label'])
            ->add(
                'appendAccountGroups',
                EntityIdentifierType::NAME,
                [
                    'class' => $this->accountGroupClass,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'removeAccountGroups',
                EntityIdentifierType::NAME,
                [
                    'class' => $this->accountGroupClass,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'appendAccounts',
                EntityIdentifierType::NAME,
                [
                    'class' => $this->accountClass,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'removeAccounts',
                EntityIdentifierType::NAME,
                [
                    'class' => $this->accountClass,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'intention' => 'payment_term',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
