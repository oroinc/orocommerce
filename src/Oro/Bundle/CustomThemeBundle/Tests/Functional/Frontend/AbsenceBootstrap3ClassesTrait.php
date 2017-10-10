<?php

namespace Oro\Bundle\CustomThemeBundle\Tests\Functional\Frontend;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @method ContainerInterface getContainer()
 */
trait AbsenceBootstrap3ClassesTrait
{
    /** @var array */
    private static $bootstrapClasses = [
        'hidden-xs', 'hidden-sm', 'hidden-md', 'hidden-lg', 'va-m_md', 'va-t_sm',
        'va-t_sm', 'container-fluid', 'row', 'col-sm-1', 'col-md-1',  'col-md-3',
        'col-md-4', 'col-sm-5', 'col-md-5', 'col-md-6', 'col-sm-7', 'col-md-7',
        'col-md-8', 'col-md-10', 'col-md-11', 'col-sm-12', 'col-md-12', 'red',
        'orange', 'base-color', 'gray', 'blue', 'w70'
    ];

    /**
     * @return array
     */
    public function themeProvider()
    {
        return  [
            'blank theme' => ['blank'],
            'default theme' => ['default'],
            'custom theme' => ['custom'],
        ];
    }

    /**
     * @param string $theme
     */
    private function setTheme($theme)
    {
        $config = $this->getContainer()->get('oro_config.global');
        $config->set('oro_frontend.frontend_theme', $theme);
        $config->flush();
    }

    /**
     * @param Crawler $crawler
     */
    private function assertBootstrapClassesNotExist(Crawler $crawler)
    {
        $crawler = $crawler->filter('.' . implode(', .', self::$bootstrapClasses));
        if ($crawler->count()) {
            $classes = explode(' ', $crawler->attr('class'));

            $this->assertTrue(false, sprintf(
                'Failed asserting that class does not exist on page: %s',
                implode(' ', array_intersect(self::$bootstrapClasses, $classes))
            ));
        }
    }
}
