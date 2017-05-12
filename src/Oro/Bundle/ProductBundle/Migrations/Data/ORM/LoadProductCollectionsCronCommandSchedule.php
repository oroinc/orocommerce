<?php

namespace Oro\Bundle\SEOBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\ProductBundle\Command\ProductCollectionsIndexCronCommand;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadProductCollectionsCronCommandSchedule extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        if ($this->isScheduleAdded()) {
            return;
        }

        $deferredScheduler = $this->container->get('oro_cron.deferred_scheduler');
        $deferredScheduler->addSchedule(
            ProductCollectionsIndexCronCommand::NAME,
            [],
            Configuration::DEFAULT_CRON_SCHEDULE
        );

        $deferredScheduler->flush();
    }

    /**
     * @return bool
     */
    private function isScheduleAdded()
    {
        $repository = $this->container
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(Schedule::class);

        return (bool) $repository->findBy(['command' => ProductCollectionsIndexCronCommand::NAME]);
    }
}
