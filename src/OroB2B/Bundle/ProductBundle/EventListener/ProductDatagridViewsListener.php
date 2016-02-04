<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
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
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $gridName = $event->getDatagrid()->getName();
        $config = $event->getConfig();
        $mixinName = $this->getMixinName($gridName);
        if ($mixinName) {
            $config->offsetAddToArray(MixinListener::MIXINS, $mixinName);
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
        $viewName = $request->query->get(sprintf('%s[%s]', $gridName, self::GRID_TEMPLATE_PATH));
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
