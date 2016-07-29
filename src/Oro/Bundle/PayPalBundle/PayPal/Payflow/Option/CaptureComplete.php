<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class CaptureComplete extends AbstractBooleanOption
{
    const CAPTURECOMPLETE = 'CAPTURECOMPLETE';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(CaptureComplete::CAPTURECOMPLETE)
            ->setNormalizer(
                CaptureComplete::CAPTURECOMPLETE,
                $this->getNormalizer(CaptureComplete::YES, CaptureComplete::NO)
            );
    }
}
