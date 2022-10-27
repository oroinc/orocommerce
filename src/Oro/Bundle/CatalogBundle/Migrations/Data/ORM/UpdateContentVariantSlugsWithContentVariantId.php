<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Updates content variant slugs to make contentVariantId route parameter from CategoryPageContentVariantType be
 * taken into account when generating slugs.
 */
class UpdateContentVariantSlugsWithContentVariantId extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function load(ObjectManager $manager): void
    {
        /** @var ContentNodeRepository $contentNodeRepo */
        $contentNodeRepo = $manager->getRepository(ContentNode::class);
        $slugGenerator = $this->container->get('oro_web_catalog.generator.slug_generator');
        $messageProducer = $this->container->get('oro_message_queue.client.message_producer');

        /** @var WebCatalog[] $webCatalogs */
        $webCatalogs = $manager->getRepository(WebCatalog::class)->findAll();
        foreach ($webCatalogs as $webCatalog) {
            $rootNode = $contentNodeRepo->getRootNodeByWebCatalog($webCatalog);
            if ($rootNode) {
                $slugGenerator->generate($rootNode);

                $manager->flush();

                $messageProducer->send(
                    WebCatalogCalculateCacheTopic::getName(),
                    [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => $webCatalog->getId()]
                );
            }
        }
    }
}
