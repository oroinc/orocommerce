<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\ContentWidget\TabbedContentWidgetType;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Rename RenameTabbedContentWidgetType from 'tabbed_content' to 'oro_tabbed_content'
 */
class RenameTabbedContentWidgetName extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function load(ObjectManager $manager)
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $qb = $manager->getConnection()->createQueryBuilder();
        $qb->update('oro_cms_content_widget')
            ->set('widget_type', ':newTabbedContentWidgetName')
            ->where($qb->expr()->eq('widget_type', ':oldTabbedContentWidgetName'))
            ->setParameter('oldTabbedContentWidgetName', 'tabbed_content')
            ->setParameter('newTabbedContentWidgetName', TabbedContentWidgetType::CONTENT_WIDGET_NAME)
            ->execute();
    }
}
