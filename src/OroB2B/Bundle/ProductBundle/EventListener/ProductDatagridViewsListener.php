<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\EventListener\MixinListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;

class ProductDatagridViewsListener
{
    const GRID_TEMPLATE_PATH = 'template';
    const MIXIN_NAME_TEMPLATE = '%s-view-%s-mixin'; // [1] grid name, [2] template name

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ConfigurationProviderInterface
     */
    protected $configProvider;

    /**
     * @param RequestStack $requestStack
     * @param ConfigurationProviderInterface $configProvider
     */
    public function __construct(RequestStack $requestStack, ConfigurationProviderInterface $configProvider)
    {
        $this->requestStack = $requestStack;
        $this->configProvider = $configProvider;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();
        $gridName = $config->getName();
        $mixinName = $this->getMixinName($gridName);
        if ($mixinName) {
            $config->offsetAddToArrayByPath(MixinListener::MIXINS, [$mixinName]);
        }
    }

    /**
     *
     * @param string $gridName
     * @return null|string
     */
    protected function getMixinName($gridName)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }
        $gridParams = $request->query->get($gridName, [self::GRID_TEMPLATE_PATH => null]);
        $viewName = $gridParams[self::GRID_TEMPLATE_PATH];

        if (!$viewName) {
            return null;
        }

        $mixinName = sprintf(self::MIXIN_NAME_TEMPLATE, $gridName, $viewName);

        return $this->mixinExists($mixinName) ? $mixinName : null;
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function mixinExists($name)
    {
        return $this->configProvider->isApplicable($name);
    }
}
