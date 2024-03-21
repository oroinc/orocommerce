<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Migrations\Data\ORM\LoadHomePageData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Updates Content Variants pointing to the system page "Oro Frontend Root (Welcome - Home page)"
 * with the Landing Page pointing to the new Homepage Landing Page.
 */
class UpdateContentVariantsWithHomepage extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    private const BATCH_SIZE = 1000;

    public function getDependencies(): array
    {
        return [
            LoadHomePageData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $homePageId = $this->getHomePageId();
        if ($homePageId === null) {
            return;
        }

        $iterator = $this->getContentVariantIdsIterator($manager);
        $iterator->setPageLoadedCallback(function (array $rows) use ($manager, $homePageId) {
            $ids = array_column($rows, 'id');

            if (empty($ids)) {
                return $rows;
            }

            $queryBuilder = $manager->createQueryBuilder();
            $queryBuilder->update(ContentVariant::class, 'cv')
                ->set('cv.type', ':contentVariantType')
                ->set('cv.systemPageRoute', ':contentVariantSystemPageRoute')
                ->set('cv.cms_page', ':homePageId')
                ->set('cv.doNotRenderTitle', ':isDoNotRenderTitle')
                ->where($queryBuilder->expr()->in('cv.id', ':ids'))
                ->setParameter('contentVariantType', CmsPageContentVariantType::TYPE)
                ->setParameter('contentVariantSystemPageRoute', null)
                ->setParameter('homePageId', $homePageId)
                ->setParameter('isDoNotRenderTitle', true)
                ->setParameter('ids', $ids)
                ->getQuery()
                ->execute();

            return $rows;
        });

        foreach ($iterator as $item) {
            // iterate over all collection to trigger `pageLoadedCallback`
        }
    }

    private function getHomePageId(): ?int
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');

        return $configManager->get('oro_cms.home_page');
    }

    private function getContentVariantIdsIterator(ObjectManager $manager): BufferedIdentityQueryResultIterator
    {
        /** @var EntityRepository $repository */
        $repository = $manager->getRepository(ContentVariant::class);
        $qb = $repository->createQueryBuilder('cv');
        $query = $qb->select('cv.id')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('cv.type', ':contentVariantType'),
                $qb->expr()->eq('cv.systemPageRoute', ':contentVariantSystemPageRoute')
            ))
            ->setParameter('contentVariantType', SystemPageContentVariantType::TYPE)
            ->setParameter('contentVariantSystemPageRoute', 'oro_frontend_root')
            ->getQuery();

        $iterator = new BufferedIdentityQueryResultIterator($query);
        $iterator->setBufferSize(self::BATCH_SIZE);

        return $iterator;
    }
}
