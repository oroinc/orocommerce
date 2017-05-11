<?php

namespace Oro\Bundle\CMSBundle\Widget;

use Psr\Log\LoggerInterface;

class WidgetRegistry
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var WidgetInterface[] */
    private $widgets = [];

    /**
     * WidgetRegistry constructor.
     * @param LoggerInterface $logger
     * @param array           $widgets
     */
    public function __construct(LoggerInterface $logger, array $widgets = [])
    {
        $this->logger = $logger;
        $this->widgets = $widgets;
    }

    /**
     * @param string          $alias
     * @param WidgetInterface $widget
     */
    public function registerWidget($alias, WidgetInterface $widget)
    {
        $this->widgets[$alias] = $widget;
    }

    /**
     * @param string $alias
     * @param array  $options
     * @return string
     */
    public function getWidget($alias, array $options = [])
    {
        if (!array_key_exists($alias, $this->widgets)) {
            $this->logger->error('Widget with alias "{alias}" not registered.', ['alias' => $alias]);

            return '';
        }

        return $this->widgets[$alias]->render($options);
    }
}
