<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\AttachmentBundle\Exception\FileNotFoundException;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlByUuidProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to work with digital assets in wysiwyg fields:
 *   - wysiwyg_image
 *   - wysiwyg_file
 */
class DigitalAssetExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('wysiwyg_image', [$this, 'getWysiwygImageUrl']),
            new TwigFunction('wysiwyg_file', [$this, 'getWysiwygFileUrl']),
        ];
    }

    /**
     * @param int $digitalAssetId This param is used on frontend
     * @param string $fileUuid
     * @param string $filterName
     * @param int $referenceType
     * @return string
     */
    public function getWysiwygImageUrl(
        int $digitalAssetId,
        string $fileUuid,
        string $filterName = 'wysiwyg_original',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        try {
            return $this->getFileUrlByUuidProvider()->getFilteredImageUrl(
                $fileUuid,
                $filterName,
                $referenceType
            );
        } catch (FileNotFoundException $e) {
            return '';
        }
    }

    public function getWysiwygFileUrl(
        int $digitalAssetId,
        string $fileUuid,
        string $action = FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        try {
            return $this->getFileUrlByUuidProvider()->getFileUrl(
                $fileUuid,
                $action,
                $referenceType
            );
        } catch (FileNotFoundException $e) {
            return '';
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            FileUrlByUuidProvider::class,
        ];
    }

    private function getFileUrlByUuidProvider(): FileUrlByUuidProvider
    {
        return $this->container->get(FileUrlByUuidProvider::class);
    }
}
