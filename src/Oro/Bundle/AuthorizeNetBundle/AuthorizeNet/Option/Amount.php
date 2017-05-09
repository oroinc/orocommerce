<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class Amount implements OptionInterface
{
    const AMOUNT = 'amount';

    /**
     * @var bool
     */
    protected $requiredOption;

    /**
     * @param bool $requiredOption
     */
    public function __construct($requiredOption = true)
    {
        $this->requiredOption = (bool)$requiredOption;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOption(OptionsResolver $resolver)
    {
        if ($this->requiredOption) {
            $resolver->setRequired(Amount::AMOUNT);
        }

        $resolver
            ->setDefined(Amount::AMOUNT)
            ->addAllowedTypes(Amount::AMOUNT, ['float', 'integer']);
    }
}
