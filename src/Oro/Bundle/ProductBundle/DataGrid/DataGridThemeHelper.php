<?php

namespace Oro\Bundle\ProductBundle\DataGrid;

use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provides a methods to get current theme for a storefront datagrid from the current request.
 */
class DataGridThemeHelper
{
    public const GRID_THEME_PARAM_NAME = 'row-view';

    public const VIEW_LIST = 'no-image-view';
    public const VIEW_GRID = 'list-view';
    public const VIEW_TILES = 'gallery-view';

    private const SESSION_KEY = 'frontend-product-grid-view';

    private RequestStack $requestStack;
    private string $defaultView;
    private array $views;

    public function __construct(RequestStack $requestStack, string $defaultView, array $views)
    {
        $this->requestStack = $requestStack;
        $this->defaultView = $defaultView;
        $this->views = $views;
    }

    public function getTheme(string $gridName): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return $this->defaultView;
        }

        $session = $this->getSession();
        if (null === $session) {
            return $this->defaultView;
        }

        $viewName = $this->defaultView;
        $gridParams = $request->query->all($gridName);
        if (\is_array($gridParams) && \array_key_exists(self::GRID_THEME_PARAM_NAME, $gridParams)) {
            $gridViewName = $gridParams[self::GRID_THEME_PARAM_NAME];
            if (\in_array($gridViewName, $this->views, true)) {
                $viewName = $gridViewName;
            }
            $session->set(self::SESSION_KEY, $viewName);
            /**
             * Value of option "Display view" is one for all frontend product grids
             * In case when user won't change this option value, we get current state from session
             */
        } elseif ($session->has(self::SESSION_KEY)) {
            $viewName = $session->get(self::SESSION_KEY);
        }

        return $viewName;
    }

    private function getSession(): ?SessionInterface
    {
        try {
            return $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return null;
        }
    }
}
