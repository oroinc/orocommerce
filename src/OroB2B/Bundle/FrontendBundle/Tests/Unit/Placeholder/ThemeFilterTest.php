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
     * @param bool $isActiveTheme
     * @param bool $isOroTheme
     * @param bool $isDemoTheme
     *
     * @dataProvider isActiveThemeDataProvider
     */
    public function testIsActiveTheme($theme, $isActiveTheme, $isOroTheme, $isDemoTheme)
    {
        $this->themeRegistry->setActiveTheme($theme);

        $this->assertEquals($isActiveTheme, $this->filter->isActiveTheme($theme));
        $this->assertEquals($isOroTheme, $this->filter->isActiveTheme('oro'));
        $this->assertEquals($isDemoTheme, $this->filter->isActiveTheme('demo'));
    }

    /**
     * @return array
     */
    public function isActiveThemeDataProvider()
    {
        return [
            'no active theme' => [
                'them' => null,
                'isActiveTheme' => false,
                'isOroTheme' => false,
                'isDemoTheme' => false,
            ],
            'unknown theme  ' => [
                'them' => 'default',
                'isActiveTheme' => true,
                'isOroTheme' => false,
                'isDemoTheme' => false,
            ],
            'oro theme' => [
                'them' => 'oro',
                'isActiveTheme' => true,
                'isOroTheme' => true,
                'isDemoTheme' => false,
            ],
            'demo theme' => [
                'them' => 'demo',
                'isActiveTheme' => true,
                'isOroTheme' => false,
                'isDemoTheme' => true,
            ],
        ];
    }
}
