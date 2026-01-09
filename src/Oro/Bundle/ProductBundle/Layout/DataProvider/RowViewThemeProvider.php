<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;

/**
 * Provides datagrid row view theme information for layout rendering.
 *
 * This data provider exposes datagrid theme configuration to layout templates, allowing templates to determine
 * which theme should be used for rendering product rows in specific datagrids.
 */
class RowViewThemeProvider
{
    /**
     * @var DataGridThemeHelper
     */
    protected $themeHelper;

    public function __construct(DataGridThemeHelper $themeHelper)
    {
        $this->themeHelper = $themeHelper;
    }

    /**
     * @param string $dataGridName
     *
     * @return null|string
     */
    public function getThemeByGridName($dataGridName)
    {
        return $this->themeHelper->getTheme($dataGridName);
    }
}
