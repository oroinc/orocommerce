<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Lists the built theme files an embedded host may dump into its own public directory, so in production the
 * host serves Oro's theme build as its own static files instead of relaying every byte through its proxy.
 * Names every built file of the themes the websites actually run, minus the source maps and the scss sidecars
 * webpack leaves behind, plus the files under the shared `_static` directory that the theme css references.
 * A theme left out still reaches the host through its proxy, so naming fewer themes costs speed, never icons.
 * The host takes the list afresh on every build of its own, so nothing here has to say which build it describes.
 * The list is computed once per asset build and theme set and served from a shared cache, so an anonymous
 * caller cannot make the server walk the build directory on every request.
 */
class EmbeddedThemeAssetsProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const string THEME_SETTING = 'oro_frontend.frontend_theme';
    private const string STYLESHEET_SUFFIX = '.css';
    private const string SHARED_FILE_REFERENCE_PATTERN = '~/build/_static/[^\s"\'()?#]+~';
    private const string PARENT_DIRECTORY = '..';
    private const string PATH_SEPARATOR = '/';
    private const string WINDOWS_PATH_SEPARATOR = '\\';
    private const string CACHE_KEY_FORMAT = 'embedded_theme_assets_%s';
    private const string CACHE_KEY_PART_SEPARATOR = '|';
    private const string THEME_NAMES_SEPARATOR = ',';

    /**
     * @param string[] $excludedFilePatterns file name patterns of build artifacts the host never needs
     */
    public function __construct(
        private readonly WebsiteProviderInterface $websiteProvider,
        private readonly ThemeConfigurationProvider $themeConfigurationProvider,
        private readonly ConfigManager $configManager,
        private readonly Packages $assetPackages,
        private readonly CacheInterface $cache,
        private readonly string $publicDirectory,
        private readonly string $buildPath = '/build',
        private readonly array $excludedFilePatterns = ['*.map', '*.scss', '*.scss.js'],
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * @return string[]
     */
    public function getFiles(): array
    {
        $themeNames = $this->resolveThemesTheWebsitesRun();

        return $this->cache->get(
            $this->resolveCacheKey($themeNames),
            fn (): array => $this->collectFiles($themeNames)
        );
    }

    /**
     * @param string[] $themeNames
     */
    private function resolveCacheKey(array $themeNames): string
    {
        $assetVersion = $this->assetPackages->getVersion($this->buildPath . self::PATH_SEPARATOR);

        return sprintf(
            self::CACHE_KEY_FORMAT,
            md5(implode(self::THEME_NAMES_SEPARATOR, $themeNames) . self::CACHE_KEY_PART_SEPARATOR . $assetVersion)
        );
    }

    /**
     * @param string[] $themeNames
     *
     * @return string[]
     */
    private function collectFiles(array $themeNames): array
    {
        $themeFiles = [];
        foreach ($themeNames as $themeName) {
            $themeFiles = array_merge(
                $themeFiles,
                $this->collectFilesUnder($this->buildPath . self::PATH_SEPARATOR . $themeName)
            );
        }

        $files = array_merge($themeFiles, $this->collectSharedFilesReferencedBy($themeFiles));
        sort($files);

        return array_values(array_unique($files));
    }

    /**
     * @return string[]
     */
    private function resolveThemesTheWebsitesRun(): array
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
    private function collectFilesUnder(string $path): array
    {
        $directory = $this->publicDirectory . $path;
        if (!is_dir($directory)) {
            $this->logger->warning('Theme build directory is missing, its files were not listed.', [
                'theme_directory' => $directory,
            ]);

            return [];
        }

        $finder = Finder::create()->files()->in($directory);
        foreach ($this->excludedFilePatterns as $pattern) {
            $finder->notName($pattern);
        }

        $files = [];
        foreach ($finder as $file) {
            $files[] = $path . self::PATH_SEPARATOR
                . str_replace(self::WINDOWS_PATH_SEPARATOR, self::PATH_SEPARATOR, $file->getRelativePathname());
        }

        return $files;
    }

    /**
     * @param string[] $themeFiles
     *
     * @return string[]
     */
    private function collectSharedFilesReferencedBy(array $themeFiles): array
    {
        $sharedFiles = [];
        foreach ($themeFiles as $themeFile) {
            if (!str_ends_with($themeFile, self::STYLESHEET_SUFFIX)) {
                continue;
            }

            $stylesheet = @file_get_contents($this->publicDirectory . $themeFile);
            if (false === $stylesheet) {
                $this->logger->error('Theme stylesheet could not be read.', ['stylesheet' => $themeFile]);
                continue;
            }

            preg_match_all(self::SHARED_FILE_REFERENCE_PATTERN, $stylesheet, $matches);
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
