<?php

namespace Oro\Bundle\DPDBundle\Form\Type;

use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RatesCsvType extends AbstractType
{
    const NAME = 'oro_dpd_rates_csv';

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $transportSettings = $form->getParent()->getData();
        $view->vars['rates_count'] =
            ($transportSettings instanceof DPDTransport) ? count($transportSettings->getRates()) : 0;
        $view->vars['download_csv_label'] = $options['download_csv_label'];
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'download_csv_label' => 'oro.dpd.transport.rates_csv.download.label',
                'constraints' => [],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return FileType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
