<?php

namespace Oro\Bundle\PromotionBundle\Context;

interface ContextDataConverterInterface
{
    const CRITERIA = 'criteria';

    /**
     * @param object $entity
     * @return array
     */
    public function getContextData($entity): array;

    /**
     * @param object $entity
     * @return bool
     */
    public function supports($entity): bool;
}
