<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;

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
