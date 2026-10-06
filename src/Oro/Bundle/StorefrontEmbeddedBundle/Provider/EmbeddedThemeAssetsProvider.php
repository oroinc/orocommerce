<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Lists the built files of the current website theme that an embedded host can copy into its own public directory.
 */
class EmbeddedThemeAssetsProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param string[] $excludedFilePatterns file name patterns of the build artifacts that the host does not need
     */
    public function __construct(
        private readonly CurrentThemeProvider $currentThemeProvider,
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
        $themeName = $this->currentThemeProvider->getCurrentThemeId();
        if (!$themeName) {
            return [];
        }

        return $this->cache->get(
            $this->resolveCacheKey($themeName),
            fn (): array => $this->collectFiles($themeName)
        );
    }

    private function resolveCacheKey(string $themeName): string
    {
        $assetVersion = $this->assetPackages->getVersion($this->buildPath . '/');

        return sprintf(
            'embedded_theme_assets_%s',
            md5($themeName . '|' . $assetVersion)
        );
    }

    /**
     * @return string[]
     */
    private function collectFiles(string $themeName): array
    {
        $themeFiles = $this->collectFilesUnder($this->buildPath . '/' . $themeName);

        $files = array_merge($themeFiles, $this->collectSharedFilesReferencedBy($themeFiles));
        sort($files);

        return array_values(array_unique($files));
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
            $files[] = $path . '/' . str_replace('\\', '/', $file->getRelativePathname());
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
            if (!str_ends_with($themeFile, '.css')) {
                continue;
            }

            $stylesheet = @file_get_contents($this->publicDirectory . $themeFile);
            if (false === $stylesheet) {
                $this->logger->error('Theme stylesheet could not be read.', ['stylesheet' => $themeFile]);
                continue;
            }

            preg_match_all('~/build/_static/[^\s"\'()?#]+~', $stylesheet, $matches);
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
        return !str_contains($path, '..') && is_file($this->publicDirectory . $path);
    }
}
