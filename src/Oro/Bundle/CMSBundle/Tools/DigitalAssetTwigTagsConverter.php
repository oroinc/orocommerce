<?php

namespace Oro\Bundle\CMSBundle\Tools;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Replaces wysiwyg_image() / wysiwyg_file() twig function with corresponding URL and vice versa.
 */
class DigitalAssetTwigTagsConverter
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var FileUrlProviderInterface */
    private $fileUrlProvider;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param FileUrlProviderInterface $fileUrlProvider
     */
    public function __construct(ManagerRegistry $managerRegistry, FileUrlProviderInterface $fileUrlProvider)
    {
        $this->managerRegistry = $managerRegistry;
        $this->fileUrlProvider = $fileUrlProvider;
    }

    /**
     * @param string $data
     * @return string
     */
    public function convertToUrls(string $data): string
    {
        return preg_replace_callback(
            '/\{\{\s*(?P<function>wysiwyg_(?:image|file))\s*\('
            . '\s*["\']?(?P<digitalAssetId>\d+?)["\']?\s*,'
            . '\s*["\']?(?P<uuid>[^"\'\)]+?)["\']?\s*'
            . '\)\s*\}\}/is',
            [$this, 'replaceToUrl'],
            $data
        );
    }

    /**
     * @param string $data
     * @return string
     */
    public function convertToTwigTags(string $data): string
    {
        return preg_replace_callback_array(
            [
                '/(?P<schema>https?:\/\/|\/\/)?(?:[a-z0-9_~:\.\/-]+?)?'
                . '\/media\/cache\/attachment\/(?:resize|filter)\/[a-z0-9\/_-]+'
                . '\/(?P<fileId>\d+?)\/[a-z0-9]+?\.[a-z0-9-]+/is' => [$this, 'replaceImageUrlToTwigTag'],
                '/(?P<schema>https?:\/\/|\/\/)?(?:[a-z0-9_~:\.\/-]+?)?'
                . '\/attachment\/(?:get|download)'
                . '\/(?P<fileId>\d+?)\/[a-z0-9]+?\.[a-z0-9-]+/is' => [$this, 'replaceFileUrlToTwigTag'],
            ],
            $data
        );
    }

    /**
     * @param array $matches
     * @return string
     */
    private function replaceToUrl(array $matches): string
    {
        $function = $matches['function'] ?? '';
        $digitalAssetId = $matches['digitalAssetId'] ?? '';
        $uuid = $matches['uuid'] ?? '';
        if ($function && $digitalAssetId && $uuid) {
            $file = $this->getFileByUuid($uuid);
            if (!$file) {
                $file = $this->getSourceFile($digitalAssetId);
            }

            if ($file) {
                if ($function === 'wysiwyg_file') {
                    $url = $this->fileUrlProvider->getFileUrl($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD);
                } else {
                    $url = $this->fileUrlProvider->getFilteredImageUrl($file, 'wysiwyg_original');
                }

                return $url;
            }
        }

        return $matches[0];
    }

    /**
     * @param string $uuid
     * @return null|File
     */
    private function getFileByUuid(string $uuid): ?File
    {
        try {
            $file = $this->managerRegistry->getManagerForClass(File::class)
                ->getRepository(File::class)
                ->findOneBy(['uuid' => $uuid]);
        } catch (\Exception $e) {
            $file = null;
        }

        return $file;
    }

    /**
     * @param int $digitalAssetId
     * @return null|File
     */
    private function getSourceFile(int $digitalAssetId): ?File
    {
        try {
            /** @var EntityManager $digitalAssetEntityManager */
            $digitalAssetEntityManager = $this->managerRegistry->getManagerForClass(DigitalAsset::class);
            $file = $digitalAssetEntityManager
                ->getRepository(DigitalAsset::class)
                ->findSourceFile($digitalAssetId);
        } catch (NonUniqueResultException | NoResultException $e) {
            $file = null;
        }

        return $file;
    }

    /**
     * @param array $matches
     * @return string
     */
    private function replaceImageUrlToTwigTag(array $matches): string
    {
        return $this->replaceToTwigTag('wysiwyg_image', $matches);
    }

    /**
     * @param array $matches
     * @return string
     */
    private function replaceFileUrlToTwigTag(array $matches): string
    {
        return $this->replaceToTwigTag('wysiwyg_file', $matches);
    }

    /**
     * @param string $function
     * @param array $matches
     * @return string
     */
    private function replaceToTwigTag(string $function, array $matches): string
    {
        if ($matches['schema']) {
            // Ignores absolute urls.
            return $matches[0];
        }

        $fileId = $matches['fileId'] ?? '';
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

                return sprintf('{{ %s(\'%d\',\'%s\') }}', $function, $digitalAssetId, $uuid);
            }
        }

        return $matches[0];
    }

    /**
     * @return string
     */
    protected function generateUuid(): string
    {
        return UUIDGenerator::v4();
    }
}
