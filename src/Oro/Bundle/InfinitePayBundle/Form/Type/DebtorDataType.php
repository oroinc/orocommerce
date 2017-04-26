<?php

namespace Oro\Bundle\InfinitePayBundle\Form\Type;

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
    const BLOCK_PREFIX = 'oro_infinite_pay_debtor_data';


    public static function getAvailableLegalTypes()
    {
        return [
            'ag' => 'AG',
            'eg' => 'eG',
            'ek' => 'EK',
            'ev' => 'e.V.',
            'freelancer' => 'Freelancer',
            'gbr' => 'GbR',
            'gmbh' => 'GmbH',
            'gmbh_ig' => 'GmbH iG',
            'gmbh_co_kg' => 'GmbH & Co. KG',
            'kg' => 'KG',
            'kgaa' => 'KgaA',
            'ltd' => 'Ltd',
            'ltd_co_kg' => 'Ltd co KG',
            'ohg' => 'OHG',
            'offtl_einrichtung' => 'öffl. Einrichtung',
            'sonst_pers_ges' => 'Sonst. KapitalGes',
            'stiftung' => 'Stiftung',
            'ug' => 'UG',
            'einzel' => 'Einzelunternehmen, Kleingewerbe, Handelsvetreter',
        ];
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
                    'choices' => self::getAvailableLegalTypes(),
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
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
