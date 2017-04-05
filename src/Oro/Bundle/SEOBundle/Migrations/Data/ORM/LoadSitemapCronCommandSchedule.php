<?php

namespace Oro\Bundle\SEOBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\SEOBundle\Command\GenerateSitemapCommand;
use Oro\Bundle\SEOBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadSitemapCronCommandSchedule extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->isScheduleAdded()) {
            $deferredScheduler = $this->container->get('oro_cron.deferred_scheduler');
            $deferredScheduler->addSchedule(GenerateSitemapCommand::NAME, [], Configuration::DEFAULT_CRON_DEFINITION);
            $deferredScheduler->flush();
        }
    }

    /**
     * @return bool
     */
    private function isScheduleAdded()
    {
        $repository = $this->container
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(Schedule::class);

        return (bool) $repository->findBy(['command' => GenerateSitemapCommand::NAME]);
    }
}
