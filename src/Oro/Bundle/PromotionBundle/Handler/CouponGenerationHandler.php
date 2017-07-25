<?php

namespace Oro\Bundle\PromotionBundle\Handler;

use Symfony\Component\Form\FormInterface;

class CouponGenerationHandler
{
    public function process(FormInterface $form)
    {
        // Get form data
        // Transfer form Data to Inserter? (or to some GenerationManager?)
        // Return needed data (true/false or generation statistic)
    }
}
