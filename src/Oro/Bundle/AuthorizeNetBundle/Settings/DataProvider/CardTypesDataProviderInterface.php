<?php

namespace Oro\Bundle\AuthorizeNetBundle\Settings\DataProvider;

interface CardTypesDataProviderInterface
{
    /**
     * @return string[]
     */
    public function getCardTypes();
}
