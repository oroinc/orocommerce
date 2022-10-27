<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\EventListener\AbstractDemoDataFixturesListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;

class UpdateSlugsDemoDataFixturesListener extends AbstractDemoDataFixturesListener
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var SlugEntityGenerator */
    protected $generator;

    /** @var UrlStorageCache */
    protected $urlStorageCache;

    public function __construct(
        OptionalListenerManager $listenerManager,
        ConfigManager $configManager,
        SlugEntityGenerator $generator,
        UrlStorageCache $urlStorageCache
    ) {
        parent::__construct($listenerManager);

        $this->configManager = $configManager;
        $this->generator = $generator;
        $this->urlStorageCache = $urlStorageCache;
    }

    /**
     * {@inheritDoc}
     */
    public function beforeEnableListeners(MigrationDataFixturesEvent $event)
    {
        $event->log('updating slugs');

        if ($this->configManager->get('oro_redirect.enable_direct_url')) {
            $this->updateSlugEntities($event->getObjectManager());
        }
        $this->updateSlugs($event->getObjectManager());
    }

    protected function updateSlugEntities(ObjectManager $manager)
    {
        foreach ($this->getSluggableClasses($manager) as $class) {
            $repository = $manager->getRepository($class);

            foreach ($repository->findAll() as $entity) {
                /* @var $entity SluggableInterface */
                $this->generator->generate($entity, $this->getCreateRedirect($entity));
            }
        }

        $this->urlStorageCache->flushAll();
    }

    protected function updateSlugs(ObjectManager $manager)
    {
        $repository = $manager->getRepository(Redirect::class);

        /* @var $slugs Slug[] */
        $slugs = $manager->getRepository(Slug::class)->findAll();
        foreach ($slugs as $slug) {
            $slugScopes = $slug->getScopes();

            /* @var $redirects Redirect[] */
            $redirects = $repository->findBy(['slug' => $slug]);

            foreach ($redirects as $redirect) {
                $redirect->setScopes($slugScopes);
            }
        }

        $manager->flush();
    }

    /**
     * @param SluggableInterface $entity
     * @return bool
     */
    protected function getCreateRedirect(SluggableInterface $entity)
    {
        if (null === $entity->getSlugPrototypesWithRedirect()) {
            return true;
        }

        return $entity->getSlugPrototypesWithRedirect()->getCreateRedirect();
    }

    /**
     * @param ObjectManager $manager
     * @return array
     */
    protected function getSluggableClasses(ObjectManager $manager)
    {
        $classes = array_filter(
            array_map(
                function (ClassMetadata $metadata) {
                    return $metadata->getName();
                },
                $manager->getMetadataFactory()->getAllMetadata()
            ),
            function ($class) {
                return is_subclass_of($class, SluggableInterface::class);
            }
        );

        return $classes;
    }
}
