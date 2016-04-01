<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class Amount implements OptionInterface
{
    const AMT = 'AMT';

    const ITEMAMT = 'ITEMAMT';
    const TAXAMT = 'TAXAMT';
    const FREIGHTAMT = 'FREIGHTAMT';
    const HANDLINGAMT = 'HANDLINGAMT';
    const INSURANCEAMT = 'INSURANCEAMT';
    const DISCOUNT = 'DISCOUNT';

    /** @var bool */
    private $amountRequired;

    /**
     * @param bool $amountRequired
     */
    public function __construct($amountRequired = true)
    {
        $this->amountRequired = $amountRequired;
    }

    /**
     * @var array
     */
    protected $additionalAmounts = [
        Amount::ITEMAMT,
        Amount::TAXAMT,
        Amount::FREIGHTAMT,
        Amount::HANDLINGAMT,
        Amount::INSURANCEAMT,
        Amount::DISCOUNT,
    ];

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $allowedTypes = ['string', 'integer', 'float'];

        if ($this->amountRequired) {
            $resolver->setRequired(Amount::AMT);
        }

        $resolver
            ->setDefined(Amount::AMT)
            ->setDefined($this->additionalAmounts)
            ->addAllowedTypes(Amount::AMT, $allowedTypes)
            ->addAllowedTypes(Amount::ITEMAMT, $allowedTypes)
            ->addAllowedTypes(Amount::TAXAMT, $allowedTypes)
            ->addAllowedTypes(Amount::FREIGHTAMT, $allowedTypes)
            ->addAllowedTypes(Amount::HANDLINGAMT, $allowedTypes)
            ->addAllowedTypes(Amount::INSURANCEAMT, $allowedTypes)
            ->addAllowedTypes(Amount::DISCOUNT, $allowedTypes)
            ->setNormalizer(
                Amount::AMT,
                function (OptionsResolver $resolver, $amount) {
                    $amounts = [];
                    foreach ($this->additionalAmounts as $key) {
                        if ($resolver->offsetExists($key)) {
                            $amounts[] = $resolver->offsetGet($key);
                        }
                    }
                    $amounts = array_filter($amounts);

                    if ($amounts) {
                        return array_sum($amounts);
                    }

                    return $amount;
                }
            );
    }
}
