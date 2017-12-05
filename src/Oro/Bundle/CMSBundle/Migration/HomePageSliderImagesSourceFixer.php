<?php

namespace Oro\Bundle\CMSBundle\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\CMSBundle\Migrations\Data\ORM\LoadHomePageSlider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * This class converts relative paths for images and links' urls in slider's content variant to absolute urls which is
 * necessary in case if application is installed in a subfolder.
 */
class HomePageSliderImagesSourceFixer
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param ConfigManager $configManager
     * @param Connection $connection
     */
    public function __construct(ConfigManager $configManager, Connection $connection)
    {
        $this->configManager = $configManager;
        $this->connection = $connection;
    }

    /**
     * @throws \LogicException
     */
    public function convertImagesPaths()
    {
        $query = <<<EOQ
SELECT variant.id, variant.content FROM oro_cms_text_content_variant variant 
INNER JOIN oro_cms_content_block block 
ON variant.content_block_id = block.id
WHERE block.alias = ? AND variant.is_default = ?
EOQ;

        $statement = $this->connection->executeQuery($query, [LoadHomePageSlider::HOME_PAGE_SLIDER_ALIAS, true]);
        $variant = $statement->fetch();

        if (!$variant) {
            throw new \LogicException('No block for home page slider is found');
        }

        $subPath = $this->getSubPath();
        if (!$subPath) {
            return;
        }

        $content = $this->processImages($variant['content'], $subPath);
        $content = $this->processLinks($content, $subPath);

        $this->connection->update(
            'oro_cms_text_content_variant',
            ['content' => $content],
            ['id' => $variant['id']]
        );
    }

    /**
     * @return string|null
     */
    private function getSubPath()
    {
        $url = $this->configManager->get('oro_ui.application_url');

        $matches = [];
        if (!preg_match('/^(?:https?\:\/\/)?[^\/\:]+(\/.+)$/', trim($url, '/'), $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @param string $content
     * @param string $subPath
     * @return string
     */
    private function processImages(string $content, string $subPath): string
    {
        return preg_replace_callback(
            '/"(\/bundles\/[^"]*\.jpg)"/',
            function ($matches) use ($subPath) {
                return sprintf('"%s%s"', $subPath, $matches[1]);
            },
            $content
        );
    }

    /**
     * @param string $content
     * @param string $subPath
     * @return string
     */
    private function processLinks(string $content, string $subPath): string
    {
        return preg_replace_callback(
            '/href=("|\')(\/[^"\']+)\1/',
            function ($matches) use ($subPath) {
                return sprintf('href=%1$s%2$s%3$s%1$s', $matches[1], $subPath, $matches[2]);
            },
            $content
        );
    }
}
