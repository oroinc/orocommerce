<?php

namespace Oro\Bundle\InfinitePayBundle\Form\Type;

use Oro\Bundle\InfinitePayBundle\Configuration\InfinitePayConfig;
use Oro\Bundle\InfinitePayBundle\Configuration\InfinitePayConfigInterface;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Email;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * {@inheritdoc}
 */
class DebtorDataType extends AbstractType
{
    const NAME = 'oro_infinite_pay_debtor_data';

    /** @var InfinitePayConfigInterface */
    protected $config;

    /**
     * DebtorDataType constructor.
     *
     * @param InfinitePayConfigInterface $config
     */
    public function __construct(InfinitePayConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'email',
                TextType::class,
                [
                    'constraints' => [new NotBlank(), new Email()],
                ]
            )
            ->add(
                'legal_form',
                ChoiceType::class,
                [
                    'choices' => InfinitePayConfig::$availableLegalTypes,
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'oro.infinite_pay.methods.debtor_data.label',
            'csrf_protection' => false,
            'attr' => [
                'data-page-component-module' => 'oroinfinitepay/js/app/components/payment-user-input-component',
            ],
        ]);
    }

    /**
     * @return string
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
