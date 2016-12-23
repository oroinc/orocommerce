<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadTranslations as BaseLoadTranslations;

class LoadTranslations extends BaseLoadTranslations
{
    /**
     * {@inheritdoc}
     */
    protected static function getTranslationPath()
    {
        return __DIR__ . '/../../../Resources/translations/workflows.en.yml';
    }
}
