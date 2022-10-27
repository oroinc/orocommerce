<?php

namespace Oro\Bundle\CMSBundle\Tools;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;
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

    /**
     * @param string $data
     * @param array{entityClass?: string, entityId?: int, fieldName?: string} $context The context in which the
     *  $data is converted. Example:
     *  [
     *      'entityClass' => 'Oro\Bundle\CMSBundle\Entity\Page',
     *      'entityId' => 42,
     *      'fieldName' => 'content',
     *  ]
     *
     * @return string
     */
    public function convertToUrls(string $data, array $context = []): string
    {
        $buffer = [];
        $context += [
            'entityClass' => '',
            'entityId' => null,
            'fieldName' => '',
        ];

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
                . '(?:,\s*["\']?(?P<extension>[a-z0-9-]*?)["\']?\s*)?'
                . ')?'
                . '\)\s*\}\}/is',
            ],
            fn (array $matches) => $this->replaceToUrl($matches, $context, $buffer),
            $data
        );
    }

    /**
     * @param string $data
     * @param array{entityClass?: string, entityId?: ?int, fieldName?: string} $context The context in which the
     *  $data is converted. Example:
     *  [
     *      'entityClass' => 'Oro\Bundle\CMSBundle\Entity\Page',
     *      'entityId' => 42,
     *      'fieldName' => 'content',
     *  ]
     *
     * @return string
     */
    public function convertToTwigTags(string $data, array $context = []): string
    {
        $buffer = [];
        $context += [
            'entityClass' => '',
            'entityId' => null,
            'fieldName' => '',
        ];

        return preg_replace_callback_array(
            [
                '/(?P<schema>https?:\/\/|\/\/)?(?:[a-z0-9_~:\.\/-]+?)?'
                . '\/media\/cache\/attachment\/(?:resize|filter)\/(?P<filterName>[a-z0-9_-]+)\/[0-9a-f]{32}'
                . '\/(?P<fileId>\d+?)\/[\w|-]+?\.(?P<extension>[a-z0-9-]+)'
                . '(?:\.(?P<extraExtension>[a-z0-9-]+))?/isu' => function (array $matches) use ($context, &$buffer) {
                    return $this->replaceImageUrlToTwigTag($matches, $context, $buffer);
                },
                '/(?P<schema>https?:\/\/|\/\/)?(?:[a-z0-9_~:\.\/-]+?)?'
                . '\/attachment\/(?P<actionName>get|download)'
                . '\/(?P<fileId>\d+?)\/[\w|-]+?\.[a-z0-9-]+/isu' => function (array $matches) use ($context, &$buffer) {
                    return $this->replaceFileUrlToTwigTag($matches, $context, $buffer);
                },
            ],
            $data
        );
    }

    private function replaceToUrl(array $matches, array $context, array &$buffer): string
    {
        $function = $matches['function'] ?? '';
        $digitalAssetId = $matches['digitalAssetId'] ?? '';
        $uuid = $matches['uuid'] ?? '';
        $filterName = $matches['filterName'] ?? '';
        $extension = $matches['extension'] ?? '';

        if ($function && $digitalAssetId) {
            if (isset($buffer[$function][$digitalAssetId][$filterName][$extension])) {
                return $buffer[$function][$digitalAssetId][$filterName][$extension];
            }

            $file = $this->getFileByUuid($uuid)
                ?? $this->managerRegistry->getRepository(DigitalAsset::class)->findSourceFile($digitalAssetId);

            if ($file) {
                if ($function === 'wysiwyg_file') {
                    $url = $this->fileUrlProvider->getFileUrl($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD);
                } else {
                    $extension = $this->chooseExtension($extension, (string) $file->getExtension());
                    $url = $this->fileUrlProvider
                        ->getFilteredImageUrl($file, $filterName ?: $this->defaultImageFilter, $extension);
                }

                $buffer[$function][$digitalAssetId][$filterName][$extension] = $url;

                return $url;
            }
        }

        return $matches[0];
    }

    private function chooseExtension(string $newExtension, string $originalExtension): string
    {
        $newExtension = FilenameExtensionHelper::canonicalizeExtension($newExtension);
        $originalExtension = FilenameExtensionHelper::canonicalizeExtension($originalExtension);

        return $newExtension !== $originalExtension ? $newExtension : '';
    }

    private function getFileByUuid(string $uuid): ?File
    {
        if (!UUIDValidator::isValidV4($uuid)) {
            throw new \InvalidArgumentException(sprintf('Invalid UUID v4: %s', $uuid));
        }

        $result = $this->managerRegistry->getRepository(File::class)->findBy(['uuid' => $uuid]);

        return $result[0] ?? null;
    }

    private function replaceImageUrlToTwigTag(array $matches, array $context, array &$buffer): string
    {
        return $this->replaceToTwigTag('wysiwyg_image', $matches, $context, $buffer);
    }

    private function replaceFileUrlToTwigTag(array $matches, array $context, array &$buffer): string
    {
        return $this->replaceToTwigTag('wysiwyg_file', $matches, $context, $buffer);
    }

    private function replaceToTwigTag(string $function, array $matches, array $context, array &$buffer): string
    {
        if ($matches['schema']) {
            // Ignores absolute urls.
            return $matches[0];
        }

        $fileId = $matches['fileId'] ?? '';
        $filterName = $matches['filterName'] ?? '';
        $extraExtension = $matches['extraExtension'] ?? '';
        if ($fileId) {
            $fileData = $this->managerRegistry->getRepository(DigitalAsset::class)->getFileDataForTwigTag($fileId);
            if ($fileData) {
                if ($fileData['parentEntityClass'] === DigitalAsset::class && $fileData['parentEntityId']) {
                    $digitalAssetId = $fileData['parentEntityId'];
                } elseif ($fileData['digitalAssetId'] && $fileData['uuid']) {
                    $digitalAssetId = $fileData['digitalAssetId'];
                } else {
                    // Returns the unchanged match as the found file is not related to a digital asset.
                    return $matches[0];
                }

                $uuid = $this->getUuid($fileData, $context, $digitalAssetId, $buffer);

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

    private function getUuid(array $fileData, array $context, int $digitalAssetId, array &$buffer): string
    {
        if ($fileData['parentEntityClass'] !== $context['entityClass']
            || $fileData['parentEntityId'] !== $context['entityId']
            || $fileData['parentEntityFieldName'] !== $context['fieldName']) {
            // Generates new uuid if the found file does not belong to the currently processed entity.
            if (!isset($buffer['uuidByDigitalAssetId'][$digitalAssetId])) {
                $buffer['uuidByDigitalAssetId'][$digitalAssetId] = $this->generateUuid();
            }

            return $buffer['uuidByDigitalAssetId'][$digitalAssetId];
        }

        return $fileData['uuid'];
    }

    protected function generateUuid(): string
    {
        return UUIDGenerator::v4();
    }
}
