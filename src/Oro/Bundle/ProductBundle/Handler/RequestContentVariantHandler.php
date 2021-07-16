<?php

namespace Oro\Bundle\ProductBundle\Handler;

use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Request content variant handler class
 */
class RequestContentVariantHandler
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return bool|integer
     */
    public function getContentVariantId()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        $value = $request->get(ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY);

        if (is_bool($value)) {
            return false;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value > 0) {
            return $value;
        }

        return false;
    }

    /**
     * @return bool|integer
     */
    public function getOverrideVariantConfiguration()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        return filter_var(
            $request->get(ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY),
            FILTER_VALIDATE_BOOLEAN
        );
    }
}
