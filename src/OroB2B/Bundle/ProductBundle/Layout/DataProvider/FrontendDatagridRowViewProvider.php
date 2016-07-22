<?php

namespace OroB2B\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;

class FrontendDatagridRowViewProvider
{
    const FRONTEND_DATAGRID_NAME = 'frontend-products-grid';

    /**
     * @var DataGridThemeHelper
     */
    protected $themeHelper;

    /**
     * @param DataGridThemeHelper $themeHelper
     */
    public function __construct(DataGridThemeHelper $themeHelper)
    {
        $this->themeHelper = $themeHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(ContextInterface $context)
    {
        return $this->themeHelper->getTheme(static::FRONTEND_DATAGRID_NAME);
    }
}
