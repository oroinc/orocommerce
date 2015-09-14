<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\Placeholder;

use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

use OroB2B\Bundle\FrontendBundle\Placeholder\ThemeFilter;

class ThemeFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ThemeRegistry
     */
    protected $themeRegistry;

    /**
     * @var ThemeFilter
     */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->themeRegistry = new ThemeRegistry([
            'default' => [],
            'oro' => [],
            'demo' => [],
        ]);

        $this->filter = new ThemeFilter($this->themeRegistry);
    }

    /**
     * @param string $theme
     * @param bool $isDefaultTheme
     * @param bool $isDemoTheme
     *
     * @dataProvider isThemeDataProvider
     */
    public function testIsTheme($theme, $isDefaultTheme, $isDemoTheme)
    {
        $this->themeRegistry->setActiveTheme($theme);

        $this->assertEquals($isDefaultTheme, $this->filter->isDefaultTheme());
        $this->assertEquals($isDemoTheme, $this->filter->isDemoTheme());
    }

    /**
     * @return array
     */
    public function isThemeDataProvider()
    {
        return [
            'no active theme' => [
                'them' => null,
                'isDefaultTheme' => true,
                'isDemoTheme' => false,
            ],
            'unknown theme  ' => [
                'them' => 'default',
                'isDefaultTheme' => true,
                'isDemoTheme' => false,
            ],
            'oro theme' => [
                'them' => 'oro',
                'isDefaultTheme' => true,
                'isDemoTheme' => false,
            ],
            'demo theme' => [
                'them' => 'demo',
                'isDefaultTheme' => false,
                'isDemoTheme' => true,
            ],
        ];
    }
}
