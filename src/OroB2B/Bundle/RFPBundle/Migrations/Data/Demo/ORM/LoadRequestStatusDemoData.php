<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class LoadRequestStatusDemoData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $statuses = array(
        array('order' => 30, 'label' => 'Pending', 'name' => 'pending'),
        array('order' => 40, 'label' => 'Assigned', 'name' => 'assigned'),
        array('order' => 50, 'label' => 'Blocked', 'name' => 'blocked'),
    );

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var \Oro\Bundle\LocaleBundle\Model\LocaleSettings $localeSettings */
        $localeSettings = $this->container->get('oro_locale.settings');

        foreach ($this->statuses as $status) {
            $entity = new RequestStatus();
            $entity->setSortOrder($status['order']);
            $entity->setName($status['name']);
            $entity->setLocale($localeSettings->getLocale())->setLabel($status['label']);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
