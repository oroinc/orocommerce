<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Symfony\Bridge\Doctrine\RegistryInterface;

class UpdateSlugsDemoDataFixturesListener
{
    const LISTENERS = [
        'oro_redirect.event_listener.slug_prototype_change',
        'oro_redirect.event_listener.slug_change',
    ];

    /** @var OptionalListenerManager */
    protected $listenerManager;

    /** @var RegistryInterface */
    protected $doctrine;

    /** @var ConfigManager */
    protected $configManager;

    /** @var SlugEntityGenerator */
    protected $generator;

    /** @var UrlStorageCache */
    protected $urlStorageCache;

    /**
     * @param OptionalListenerManager $listenerManager
     * @param RegistryInterface $doctrine
     * @param ConfigManager $configManager
     * @param SlugEntityGenerator $generator
     * @param UrlStorageCache $urlStorageCache
     */
    public function __construct(
        OptionalListenerManager $listenerManager,
        RegistryInterface $doctrine,
        ConfigManager $configManager,
        SlugEntityGenerator $generator,
        UrlStorageCache $urlStorageCache
    ) {
        $this->listenerManager = $listenerManager;
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
        $this->generator = $generator;
        $this->urlStorageCache = $urlStorageCache;
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPreLoad(MigrationDataFixturesEvent $event)
    {
        if (!$event->isDemoFixtures()) {
            return;
        }

        $this->listenerManager->disableListeners(self::LISTENERS);
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        if (!$event->isDemoFixtures()) {
            return;
        }

        $event->log('updating slugs');

        if ($this->configManager->get('oro_redirect.enable_direct_url')) {
            $this->updateSlugEntities();
        }
        $this->updateSlugs();

        $this->listenerManager->enableListeners(self::LISTENERS);
    }

    protected function updateSlugEntities()
    {
        foreach ($this->getSluggableClasses() as $class) {
            $repository = $this->getRepository($class);

            foreach ($repository->findAll() as $entity) {
                /* @var $entity SluggableInterface */
                $this->generator->generate($entity, $this->getCreateRedirect($entity));
            }
        }

        $this->urlStorageCache->flushAll();
    }

    protected function updateSlugs()
    {
        $manager = $this->getObjectManager(Redirect::class);
        $repository = $this->getRepository(Redirect::class);

        /* @var $slugs Slug[] */
        $slugs = $this->getRepository(Slug::class)->findAll();
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
     * @return array
     */
    protected function getSluggableClasses()
    {
        $classes = array_filter(
            array_map(
                function (ClassMetadata $metadata) {
                    return $metadata->getName();
                },
                $this->doctrine->getEntityManager()->getMetadataFactory()->getAllMetadata()
            ),
            function ($class) {
                return is_subclass_of($class, SluggableInterface::class);
            }
        );

        return $classes;
    }

    /**
     * @param string $className
     * @return ObjectManager
     */
    protected function getObjectManager($className)
    {
        return $this->doctrine->getManagerForClass($className);
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getObjectManager($className)->getRepository($className);
    }
}
