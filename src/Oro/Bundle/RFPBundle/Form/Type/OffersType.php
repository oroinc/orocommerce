<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting offers from RFP request product items.
 *
 * This form type extends {@see ChoiceType} to present available offers (quantity/unit/price combinations
 * from {@see RequestProductItem} entities) as selectable choices when creating quotes or orders from an RFP request.
 * The offers are passed via the 'offers' option and rendered as expanded radio buttons.
 */
class OffersType extends AbstractType
{
    public const NAME = 'oro_rfp_request_offers';

    public const OFFERS_OPTION = 'offers';

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['offers'] = $options['offers'];
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'mapped' => false,
                'expanded' => true,
                self::OFFERS_OPTION => [],
            ]
        );

        $resolver->setDefined(self::OFFERS_OPTION);
        $resolver->setAllowedTypes(self::OFFERS_OPTION, 'array');

        $resolver->setNormalizer(
            'choices',
            function (Options $options) {
                return array_keys($options['offers']);
            }
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
