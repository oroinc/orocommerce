<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Partner;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\PayPal;

class PartnerTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Partner()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [
                [],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                    'The required option "PARTNER" is missing.',
                ],
            ],
            'invalid type' => [
                ['PARTNER' => 123],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "PARTNER" with value 123 is invalid. Accepted values are: "PayPal", "PayPalCA",' .
                    ' "AMEX", "MESP", "NOVA", "NASH", "NORT", "SOUT", "MAPP", "NDCE", "HTLD", "LITL", "MONE", "PAYT",' .
                    ' "TMPA", "PPAY", "SNET", "VITA", "TELN", "FIFT", "VSA", "WPAY".',
                ],
            ],
            'valid' => [['PARTNER' => 'PayPal'], ['PARTNER' => 'PayPal']],
        ];
    }

    public function testList()
    {
        $this->assertInternalType('array', Partner::$partners);
        $this->assertNotEmpty('array', Partner::$partners);
        $this->assertArrayHasKey(PayPal::CODE, Partner::$partners);
    }
}
