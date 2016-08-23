<?php

namespace Oro\Bundle\ProductBundle\DataGrid;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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

    /**
     * @param RequestStack $requestStack
     * @param SessionInterface $session
     */
    public function __construct(RequestStack $requestStack, SessionInterface $session)
    {
        $this->requestStack = $requestStack;
        $this->session = $session;
    }

    /**
     *
     * @param string $gridName
     * @return null|string
     */
    public function getTheme($gridName)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $this->getDefaultView();
        }
        $viewName = $this->getDefaultView();
        $gridParams = $request->query->get($gridName);
        if (is_array($gridParams) && array_key_exists(self::GRID_THEME_PARAM_NAME, $gridParams)) {
            $viewName = $gridParams[self::GRID_THEME_PARAM_NAME];
            $this->session->set(self::SESSION_KEY, $viewName);
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
}
