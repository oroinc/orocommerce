<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Symfony\Component\HttpFoundation\RequestStack;
use Oro\Bundle\UIBundle\Route\Router;

class DiscountRecalculationProvider
{
    const SAVE_WITHOUT_DISCOUNTS_RECALCULATION_INPUT_ACTION = 'save_without_discounts_recalculation';

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return bool
     */
    public function isRecalculationRequired()
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return true;
        }

        return $request->get(Router::ACTION_PARAMETER) !== self::SAVE_WITHOUT_DISCOUNTS_RECALCULATION_INPUT_ACTION;
    }
}
