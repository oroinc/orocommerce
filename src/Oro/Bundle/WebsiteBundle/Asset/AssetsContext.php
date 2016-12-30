<?php

namespace Oro\Bundle\WebsiteBundle\Asset;

use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\RequestStack;

class AssetsContext extends RequestStackContext
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        parent::__construct($requestStack);
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePath()
    {
        $defaultBasePath = parent::getBasePath();
        $masterRequest = $this->requestStack->getMasterRequest();
        if ($masterRequest && $configuredPath = $masterRequest->server->get('WEBSITE_PATH')) {
            return str_replace($configuredPath, '', $defaultBasePath);
        }

        return $defaultBasePath;
    }
}
