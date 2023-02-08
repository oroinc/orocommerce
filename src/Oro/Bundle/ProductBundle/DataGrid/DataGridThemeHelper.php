<?php

namespace Oro\Bundle\ProductBundle\DataGrid;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provides a methods to get current theme from request.
 */
class DataGridThemeHelper
{
    const GRID_THEME_PARAM_NAME = 'row-view';

    const VIEW_LIST = 'no-image-view';
    const VIEW_GRID = 'list-view';
    const VIEW_TILES = 'gallery-view';

    const SESSION_KEY = 'frontend-product-grid-view';

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var SessionInterface
     */
    protected $session;

    public function __construct(RequestStack $requestStack, SessionInterface $session)
    {
        $this->requestStack = $requestStack;
        $this->session = $session;
    }

    /**
     * @param string $gridName
     * @return null|string
     */
    public function getTheme(string $gridName)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $this->getDefaultView();
        }
        $viewName = $this->getDefaultView();
        $gridParams = $request->query->get($gridName);
        if (is_array($gridParams) && array_key_exists(self::GRID_THEME_PARAM_NAME, $gridParams)) {
            $viewName = $gridParams[self::GRID_THEME_PARAM_NAME];
            if (!in_array($viewName, $this->getViewList())) {
                $viewName = $this->getDefaultView();
            }
            $this->session->set(self::SESSION_KEY, $viewName);
            /**
             * Value of option "Display view" is one for all frontend product grids
             * In case when user won't change this option value, we get current state from session
             */
        } elseif ($this->session->has(self::SESSION_KEY)) {
            $viewName = $this->session->get(self::SESSION_KEY);
        }
        return $viewName;
    }

    /**
     * @return string
     */
    protected function getDefaultView()
    {
        return self::VIEW_GRID;
    }

    /**
     * @return array
     */
    protected function getViewList()
    {
        return [
            self::VIEW_LIST,
            self::VIEW_GRID,
            self::VIEW_TILES,
        ];
    }
}
