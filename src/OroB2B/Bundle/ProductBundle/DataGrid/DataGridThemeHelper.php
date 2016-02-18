<?php

namespace OroB2B\Bundle\ProductBundle\DataGrid;

use Symfony\Component\HttpFoundation\RequestStack;

class DataGridThemeHelper
{
    const GRID_THEME_PARAM_NAME = 'template';

    const VIEW_GRID = 'grid';
    const VIEW_TILES = 'tiles';
    const VIEW_LIST = 'list';

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
        $gridParams = $request->query->get($gridName);

        if (is_array($gridParams) && array_key_exists(self::GRID_THEME_PARAM_NAME, $gridParams)) {
            return $gridParams[self::GRID_THEME_PARAM_NAME];
        } else {
            return $this->getDefaultView();
        }
    }

    /**
     * @return string
     */
    protected function getDefaultView()
    {
        return self::VIEW_GRID;
    }
}
