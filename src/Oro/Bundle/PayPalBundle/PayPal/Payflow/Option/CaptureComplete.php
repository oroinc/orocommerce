<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures capture complete option for PayPal Payflow transactions.
 *
 * Controls whether to mark an authorization as complete for capture,
 * preventing additional authorizations on the same transaction.
 */
class CaptureComplete extends AbstractBooleanOption
{
    const CAPTURECOMPLETE = 'CAPTURECOMPLETE';

    #[\Override]
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
