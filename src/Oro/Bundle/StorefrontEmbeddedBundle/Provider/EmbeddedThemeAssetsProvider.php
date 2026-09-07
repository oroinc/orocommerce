<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Symfony\Component\Finder\Finder;

/**
 * Lists the built theme files an embedded host may dump into its own public directory, so in production the
 * host serves Oro's theme build as its own static files instead of relaying every byte through its proxy.
 * Names every built file of the themes the websites actually run, minus the source maps and the scss sidecars
 * webpack leaves behind, plus the files under the shared `_static` directory that the theme css references.
 * A theme left out still reaches the host through its proxy, so naming fewer themes costs speed, never icons.
 * The host takes the list afresh on every build of its own, so nothing here has to say which build it describes.
 */
class EmbeddedThemeAssetsProvider
{
    private const string THEME_SETTING = 'oro_frontend.frontend_theme';
    private const string BUILD_PATH = '/build';
    private const array EXCLUDED_FILE_PATTERNS = ['*.map', '*.scss', '*.scss.js'];
    private const string STYLESHEET_SUFFIX = '.css';
    private const string SHARED_FILE_REFERENCE = '~/build/_static/[^\s"\'()?#]+~';
    private const string PARENT_DIRECTORY = '..';

    public function __construct(
        private readonly WebsiteProviderInterface $websiteProvider,
        private readonly ThemeConfigurationProvider $themeConfigurationProvider,
        private readonly ConfigManager $configManager,
        private readonly string $publicDirectory,
    ) {
    }

    /**
     * @return string[]
     */
    public function getFiles(): array
    {
        $themeFiles = [];
        foreach ($this->themesTheWebsitesRun() as $themeName) {
            $themeFiles = array_merge($themeFiles, $this->filesUnder(self::BUILD_PATH . '/' . $themeName));
        }

        $files = array_merge($themeFiles, $this->sharedFilesReferencedBy($themeFiles));
        sort($files);

        return array_values(array_unique($files));
    }

    /**
     * @return string[]
     */
    private function themesTheWebsitesRun(): array
    {
        $themeNames = [];
        foreach ($this->websiteProvider->getWebsites() as $website) {
            $themeNames[] = $this->themeConfigurationProvider->getThemeName($website)
                ?? $this->configManager->get(self::THEME_SETTING, false, false, $website);
        }

        $themeNames[] = $this->configManager->get(self::THEME_SETTING);

        return array_values(array_unique(array_filter($themeNames)));
    }

    /**
     * @return string[]
     */
    private function filesUnder(string $path): array
    {
        $directory = $this->publicDirectory . $path;
        if (!is_dir($directory)) {
            return [];
        }

        $finder = Finder::create()->files()->in($directory);
        foreach (self::EXCLUDED_FILE_PATTERNS as $pattern) {
            $finder->notName($pattern);
        }

        $files = [];
        foreach ($finder as $file) {
            $files[] = $path . '/' . str_replace('\\', '/', $file->getRelativePathname());
        }

        return $files;
    }

    /**
     * @param string[] $themeFiles
     *
     * @return string[]
     */
    private function sharedFilesReferencedBy(array $themeFiles): array
    {
        $sharedFiles = [];
        foreach ($themeFiles as $themeFile) {
            if (!str_ends_with($themeFile, self::STYLESHEET_SUFFIX)) {
                continue;
            }

            $stylesheet = (string) file_get_contents($this->publicDirectory . $themeFile);
            preg_match_all(self::SHARED_FILE_REFERENCE, $stylesheet, $matches);

            foreach ($matches[0] as $reference) {
                if ($this->isExistingBuildFile($reference)) {
                    $sharedFiles[] = $reference;
                }
            }
        }

        return $sharedFiles;
    }

    private function isExistingBuildFile(string $path): bool
    {
        return !str_contains($path, self::PARENT_DIRECTORY) && is_file($this->publicDirectory . $path);
    }
}
