<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Calculator;

use OroB2B\Bundle\TaxBundle\Calculator\IncludedTaxCalculator;

class IncludedTaxCalculatorTest extends AbstractTaxCalculatorTest
{
    /**
     * @return array
     *
     * @link http://salestax.avalara.com/
     */
    public function calculateDataProvider()
    {
        return [
            // use cases
            'Finney County' => [['17.21', '15.986994890', '1.223005110', '0.003005110'], '17.21', '0.0765'],
            'Fremont County' => [['59.04', '56.228571428', '2.811428572', '0.001428572'], '59.04', '0.05'],
            'Tulare County' => [['14.41', '13.342592592', '1.067407408', '0.007407408'], '14.41', '0.08'],
            'Mclean County' => [['35.88', '33.769411764', '2.110588236', '0.000588236'], '35.88', '0.0625'],

            // edge cases
            [['15.98', '7.99', '7.99', '0'], '15.98', '1'],
            [['15.98', '5.326666666', '10.653333334', '0.003333334'], '15.98', '2'],
            [['15.98', '8.030150753', '7.949849247', '0.009849247'], '15.98', '0.99'],
            [['15.98', '15.964035964', '0.015964036', '0.005964036'], '15.98', '0.001'],
            [['15.98', '15.956065901', '0.023934099', '0.003934099'], '15.98', '0.0015'],
            [['15.98', '13.316666666', '2.663333334', '0.003333334'], '15.98', '-0.2'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCalculator()
    {
        return new IncludedTaxCalculator();
    }
}
