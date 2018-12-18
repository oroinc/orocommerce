<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Remove scope with empty criteria from non-default Web Catalog Content Variants.
 * Schedule Web Catalog tree cache recalculation
 */
class RecalculateDefaultVariantScopes extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ContentNodeRepository $webCatalogRepository */
        $contentNodeRepo = $manager->getRepository(ContentNode::class);
        $slugGenerator = $this->container->get('oro_web_catalog.generator.slug_generator');
        $messageProducer = $this->container->get('oro_message_queue.client.message_producer');

        $webCatalogs = $manager->getRepository(WebCatalog::class)->findAll();
        foreach ($webCatalogs as $webCatalog) {
            $rootNode = $contentNodeRepo->getRootNodeByWebCatalog($webCatalog);

            $this->updateVariantScopes($webCatalog, $contentNodeRepo);
            $slugGenerator->generate($rootNode);

            $manager->flush();

            $messageProducer->send(Topics::CALCULATE_WEB_CATALOG_CACHE, $webCatalog->getId());
        }
    }

    /**
     * @param WebCatalog $webCatalog
     * @param ContentNodeRepository $nodeRepository
     */
    protected function updateVariantScopes(WebCatalog $webCatalog, ContentNodeRepository $nodeRepository)
    {
        $defaultVariantScopeResolver = $this->container->get('oro_web_catalog.resolver.default_variant_scope');

        /** @var ContentNode[] $nodes */
        $nodes = $nodeRepository->findBy(['webCatalog' => $webCatalog]);
        foreach ($nodes as $node) {
            $defaultVariantScopeResolver->resolve($node);
        }
    }
}
