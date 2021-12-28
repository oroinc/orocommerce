<?php

namespace Oro\Bundle\CMSBundle\Tools;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\SecurityBundle\Tools\UUIDValidator;

/**
 * Replaces wysiwyg_image() / wysiwyg_file() twig function with corresponding URL and vice versa.
 */
class DigitalAssetTwigTagsConverter
{
    private ManagerRegistry $managerRegistry;

    private FileUrlProviderInterface $fileUrlProvider;

    private string $defaultImageFilter;

    public function __construct(
        ManagerRegistry $managerRegistry,
        FileUrlProviderInterface $fileUrlProvider,
        string $defaultImageFilter = 'wysiwyg_original'
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->fileUrlProvider = $fileUrlProvider;
        $this->defaultImageFilter = $defaultImageFilter;
    }

    public function convertToUrls(string $data): string
    {
        return preg_replace_callback(
            [
                '/\{\{\s*(?P<function>wysiwyg_file)\s*\('
                . '\s*["\']?(?P<digitalAssetId>\d+?)["\']?\s*'
                . ',\s*["\']?(?P<uuid>[^"\'\)]+?)["\']?\s*'
                . '\)\s*\}\}/is',
                '/\{\{\s*(?P<function>wysiwyg_image)\s*\('
                . '\s*["\']?(?P<digitalAssetId>\d+?)["\']?\s*'
                . ',\s*["\']?(?P<uuid>[^"\'\)]+?)["\']?\s*'
                . '(?:'
                . ',\s*["\']?(?P<filterName>[a-z0-9_-]*?)["\']?\s*'
                . '(?:,\s*["\']?(?P<format>[a-z0-9-]*?)["\']?\s*)?'
                . ')?'
                . '\)\s*\}\}/is',
            ],
            [$this, 'replaceToUrl'],
            $data
        );
    }

    public function convertToTwigTags(string $data): string
    {
        return preg_replace_callback_array(
            [
                '/(?P<schema>https?:\/\/|\/\/)?(?:[a-z0-9_~:\.\/-]+?)?'
                . '\/media\/cache\/attachment\/(?:resize|filter)\/(?P<filterName>[a-z0-9_-]+)\/[0-9a-f]{32}'
                . '\/(?P<fileId>\d+?)\/[a-z0-9]+?\.(?P<extension>[a-z0-9-]+)'
                . '(?:\.(?P<extraExtension>[a-z0-9-]+))?/is' => [$this, 'replaceImageUrlToTwigTag'],
                '/(?P<schema>https?:\/\/|\/\/)?(?:[a-z0-9_~:\.\/-]+?)?'
                . '\/attachment\/(?:get|download)'
                . '\/(?P<fileId>\d+?)\/[a-z0-9]+?\.[a-z0-9-]+/is' => [$this, 'replaceFileUrlToTwigTag'],
            ],
            $data
        );
    }

    private function replaceToUrl(array $matches): string
    {
        $function = $matches['function'] ?? '';
        $digitalAssetId = $matches['digitalAssetId'] ?? '';
        $uuid = $matches['uuid'] ?? '';
        $filterName = $matches['filterName'] ?? '';
        $format = $matches['format'] ?? '';
        if ($function && $digitalAssetId && $uuid) {
            $file = $this->getFileByUuid($uuid);
            if (!$file) {
                /** @var EntityManager $digitalAssetEntityManager */
                $digitalAssetEntityManager = $this->managerRegistry->getManagerForClass(DigitalAsset::class);
                $file = $digitalAssetEntityManager
                    ->getRepository(DigitalAsset::class)
                    ->findSourceFile($digitalAssetId);
            }

            if ($file) {
                if ($function === 'wysiwyg_file') {
                    $url = $this->fileUrlProvider->getFileUrl($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD);
                } else {
                    $url = $this->fileUrlProvider->getFilteredImageUrl(
                        $file,
                        $filterName ?: $this->defaultImageFilter,
                        $format !== $file->getExtension() ? $format : ''
                    );
                }

                return $url;
            }
        }

        return $matches[0];
    }

    private function getFileByUuid(string $uuid): ?File
    {
        if (!UUIDValidator::isValidV4($uuid)) {
            throw new \InvalidArgumentException(sprintf('Invalid UUID v4: %s', $uuid));
        }

        try {
            $file = $this->managerRegistry->getManagerForClass(File::class)
                ->getRepository(File::class)
                ->findOneBy(['uuid' => $uuid]);
        } catch (\Exception $e) {
            $file = null;
        }

        return $file;
    }

    private function replaceImageUrlToTwigTag(array $matches): string
    {
        return $this->replaceToTwigTag('wysiwyg_image', $matches);
    }

    private function replaceFileUrlToTwigTag(array $matches): string
    {
        return $this->replaceToTwigTag('wysiwyg_file', $matches);
    }

    private function replaceToTwigTag(string $function, array $matches): string
    {
        if ($matches['schema']) {
            // Ignores absolute urls.
            return $matches[0];
        }

        $fileId = $matches['fileId'] ?? '';
        $filterName = $matches['filterName'] ?? '';
        $extraExtension = $matches['extraExtension'] ?? '';
        if ($fileId) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->managerRegistry->getManagerForClass(DigitalAsset::class);
            $fileData = $entityManager->getRepository(DigitalAsset::class)->getFileDataForTwigTag($fileId);
            if ($fileData) {
                if ($fileData['parentEntityClass'] === DigitalAsset::class && $fileData['parentEntityId']) {
                    $digitalAssetId = $fileData['parentEntityId'];
                    $uuid = $this->generateUuid();
                } elseif ($fileData['digitalAssetId'] && $fileData['uuid']) {
                    $digitalAssetId = $fileData['digitalAssetId'];
                    $uuid = $fileData['uuid'];
                } else {
                    return $matches[0];
                }

                return match ($function) {
                    'wysiwyg_file' => sprintf('{{ wysiwyg_file(\'%d\',\'%s\') }}', $digitalAssetId, $uuid),
                    'wysiwyg_image' => sprintf(
                        '{{ wysiwyg_image(\'%d\',\'%s\',\'%s\',\'%s\') }}',
                        $digitalAssetId,
                        $uuid,
                        $filterName ?: $this->defaultImageFilter,
                        $extraExtension
                    )
                };
            }
        }

        return $matches[0];
    }

    protected function generateUuid(): string
    {
        return UUIDGenerator::v4();
    }
}
