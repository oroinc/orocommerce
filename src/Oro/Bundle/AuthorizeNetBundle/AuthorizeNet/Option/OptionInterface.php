<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

interface OptionInterface
{
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOption(OptionsResolver $resolver);
}
