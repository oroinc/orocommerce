<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class OriginalTransaction implements OptionInterface
{
    const ORIGINAL_TRANSACTION = 'original_transaction';

    /**
     * @var bool
     */
    protected $requiredOption;

    /**
     * @param bool $requiredOption
     */
    public function __construct($requiredOption = true)
    {
        $this->requiredOption = $requiredOption;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOption(OptionsResolver $resolver)
    {
        if ($this->requiredOption) {
            $resolver->setRequired(OriginalTransaction::ORIGINAL_TRANSACTION);
        }

        $resolver
            ->setDefined(OriginalTransaction::ORIGINAL_TRANSACTION)
            ->addAllowedTypes(OriginalTransaction::ORIGINAL_TRANSACTION, ['integer','string']);
    }
}
