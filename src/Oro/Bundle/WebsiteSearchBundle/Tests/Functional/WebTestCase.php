<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional;

use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase as BaseWebTestCase;

class WebTestCase extends BaseWebTestCase
{
    /**
     * @param array $classNames
     * @param bool  $force
     * @param string $entityManager
     */
    protected function loadFixtures(array $classNames, $force = false, $entityManager = 'default')
    {
        //TODO: Consider to add optional parameter $entityManager to BaseWebTestCase to avoid copy paste
        if (!$force) {
            $classNames = array_filter(
                $classNames,
                function ($value) {
                    return !in_array($value, self::$loadedFixtures);
                }
            );

            if (!$classNames) {
                return;
            }
        }

        self::$loadedFixtures = array_merge(self::$loadedFixtures, $classNames);

        $loader = $this->getFixtureLoader($classNames);
        $fixtures = array_values($loader->getFixtures());

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager($entityManager);
        $executor = new ORMExecutor($em, new ORMPurger($em));
        $executor->execute($fixtures, true);
        self::$referenceRepository = $executor->getReferenceRepository();
        $this->postFixtureLoad();
    }

    /**
     * Retrieve Doctrine DataFixtures loader.
     *
     * @param array $classNames
     *
     * @return DataFixturesLoader
     */
    private function getFixtureLoader(array $classNames)
    {
        $loader = new DataFixturesLoader($this->getContainer());

        foreach ($classNames as $className) {
            $this->loadFixtureClass($loader, $className);
        }

        return $loader;
    }

    /**
     * Load a data fixture class.
     *
     * @param DataFixturesLoader $loader
     * @param string             $className
     */
    private function loadFixtureClass(DataFixturesLoader $loader, $className)
    {
        $fixture = new $className();

        if ($loader->hasFixture($fixture)) {
            unset($fixture);
            return;
        }

        $loader->addFixture($fixture);

        if ($fixture instanceof DependentFixtureInterface) {
            foreach ($fixture->getDependencies() as $dependency) {
                $this->loadFixtureClass($loader, $dependency);
            }
        }
    }
}
