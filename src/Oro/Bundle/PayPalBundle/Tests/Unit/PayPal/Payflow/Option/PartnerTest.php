<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Partner;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\PayPal;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class PartnerTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new Partner()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [
                [],
                [],
                [
                    MissingOptionsException::class,
                    'The required option "PARTNER" is missing.',
                ],
            ],
            'invalid type' => [
                ['PARTNER' => 123],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "PARTNER" with value 123 is invalid. Accepted values are: "PayPal", "PayPalCA",'
                    . ' "AMEX", "MESP", "NOVA", "NASH", "NORT", "SOUT", "MAPP", "NDCE", "HTLD", "LITL", "MONE", "PAYT",'
                    . ' "TMPA", "PPAY", "SNET", "VITA", "TELN", "FIFT", "VSA", "WPAY".',
                ],
            ],
            'valid' => [['PARTNER' => 'PayPal'], ['PARTNER' => 'PayPal']],
        ];
    }

    public function testList()
    {
        $this->assertIsArray(Partner::$partners);
        $this->assertNotEmpty(Partner::$partners);
        $this->assertArrayHasKey(PayPal::CODE, Partner::$partners);
    }
}
