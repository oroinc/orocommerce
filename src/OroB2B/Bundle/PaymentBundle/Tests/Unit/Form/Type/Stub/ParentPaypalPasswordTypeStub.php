<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ParentPaypalPasswordTypeStub extends AbstractType
{
    const NAME = 'orob2b_payment_test_parent_paypal_password_type_stub';

    /** @var FormBuilderInterface */
    protected $passwordFormBuilder;

    /**
     * {@inheritdoc}
     */
    public function __construct(FormBuilderInterface $passwordFormBuilder)
    {
        $this->passwordFormBuilder = $passwordFormBuilder;
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add($this->passwordFormBuilder);
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
