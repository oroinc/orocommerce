<?php

namespace OroB2B\Bundle\FrontendBundle\Twig;

use Assetic\Factory\AssetFactory;

use Oro\Bundle\AsseticBundle\AssetsConfiguration;
use Oro\Bundle\AsseticBundle\Twig\AsseticTokenParser;
use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

use OroB2B\Bundle\FrontendBundle\EventListener\ThemeListener;

class FrontendAsseticTokenParser extends AsseticTokenParser
{
    /**
     * @var ThemeRegistry
     */
    protected $themeRegistry;

    /**
     * @param AssetsConfiguration $assetsConfiguration
     * @param AssetFactory $assetFactory
     * @param ThemeRegistry $themeRegistry
     * @param string $tag
     * @param string $output
     */
    public function __construct(
        AssetsConfiguration $assetsConfiguration,
        AssetFactory $assetFactory,
        ThemeRegistry $themeRegistry,
        $tag,
        $output
    ) {
        parent::__construct($assetsConfiguration, $assetFactory, $tag, $output);

        $this->themeRegistry = $themeRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function createAsseticNode(
        \Twig_NodeInterface $body,
        array $filters,
        array $attributes,
        $lineno
    ) {
        $currentTheme = $this->themeRegistry->getActiveTheme()->getName();
        $this->themeRegistry->setActiveTheme(ThemeListener::FRONTEND_THEME);

        $node = parent::createAsseticNode($body, $filters, $attributes, $lineno);

        $this->themeRegistry->setActiveTheme($currentTheme);

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    protected function createDebugAsseticNode(
        \Twig_NodeInterface $body,
        array $filters,
        array $attributes,
        $lineno
    ) {
        $currentTheme = $this->themeRegistry->getActiveTheme()->getName();
        $this->themeRegistry->setActiveTheme(ThemeListener::FRONTEND_THEME);

        $node = parent::createDebugAsseticNode($body, $filters, $attributes, $lineno);

        $this->themeRegistry->setActiveTheme($currentTheme);

        return $node;
    }
}
