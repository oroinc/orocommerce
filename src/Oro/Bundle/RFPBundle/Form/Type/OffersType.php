<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OffersType extends AbstractType
{
    const NAME = 'oro_rfp_request_offers';

    const OFFERS_OPTION = 'offers';

    /** {@inheritdoc} */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['offers'] = $options['offers'];
    }

    /** {@inheritdoc} */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'mapped' => false,
                'expanded' => true,
                'choices_as_values' => true,
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

    /** {@inheritdoc} */
    public function getParent()
    {
        return 'choice';
    }

    /** {@inheritdoc} */
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
