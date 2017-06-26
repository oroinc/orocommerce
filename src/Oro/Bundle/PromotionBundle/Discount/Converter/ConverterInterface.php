<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

interface ConverterInterface
{
    /**
     * @param object $sourceEntity
     * @return mixed
     */
    public function convert($sourceEntity);

    /**
     * @param object $sourceEntity
     * @return bool
     */
    public function supports($sourceEntity): bool;
}
