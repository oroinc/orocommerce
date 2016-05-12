<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\TaxBundle\Tests\ResultComparatorTrait;

/**
 * @dbIsolation
 */
class TaxManagerTest extends WebTestCase
{
    use ResultComparatorTrait;

    /** @var ConfigManager */
    protected $configManager;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var ManagerRegistry */
    protected $doctrine;

    protected function setUp()
    {
        $this->initClient([], [], true);

        $this->loadFixtures(
            [
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules',
            ],
            true // self::resetClient doesn't clear loaded fixtures
        );

        $this->configManager = $this->getContainer()->get('oro_config.global');
        $this->propertyAccessor = $this->getContainer()->get('property_accessor');
        $this->doctrine = $this->getContainer()->get('doctrine');
    }

    protected function tearDown()
    {
        $this->configManager->reload();

        parent::tearDown();
    }

    /**
     * @dataProvider methodsDataProvider
     * @param string $method
     * @param string $reference
     * @param array $configuration
     * @param array $databaseBefore
     * @param array $expectedResult
     * @param array $databaseAfter
     */
    public function testMethods(
        $method,
        $reference,
        array $configuration,
        array $databaseBefore = [],
        array $expectedResult = [],
        array $databaseAfter = []
    ) {
        foreach ($configuration as $key => $value) {
            $this->configManager->set(sprintf('orob2b_tax.%s', $key), $value);
        }

        $this->prepareDatabase($databaseBefore);

        $this->executeMethod($method, $this->getReference($reference), $expectedResult);

        $this->assertDatabase($databaseAfter);
    }

    /**
     * @return array
     */
    public function methodsDataProvider()
    {
        $finder = new Finder();

        $finder
            ->files()
            ->in(__DIR__)
            ->name('*.yml');

        $cases = [];

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $cases[$file->getRelativePathname()] = Yaml::parse($file->getContents());
        }

        return $cases;
    }

    /**
     * @param string $method
     * @param object $object
     * @param array $expectedResult
     */
    protected function executeMethod($method, $object, $expectedResult)
    {
        $manager = $this->getContainer()->get('orob2b_tax.manager.tax_manager');

        $this->compareResult($expectedResult, $manager->{$method}($object, true));

        // cache trigger
        $this->compareResult($expectedResult, $manager->{$method}($object, true));
    }

    /**
     * @param array $databaseBefore
     */
    protected function prepareDatabase(array $databaseBefore)
    {
        // Disable taxation for load fixtures
        $previousTaxEnableState = $this->configManager->get('orob2b_tax.tax_enable');
        $this->configManager->set('orob2b_tax.tax_enable', false);

        foreach ($databaseBefore as $class => $items) {
            /** @var EntityManager $em */
            $em = $this->doctrine->getManagerForClass($class);

            foreach ($items as $reference => $item) {
                $object = new $class();

                $this->fillData($object, $item);

                $em->persist($object);
                $em->flush($object);

                $this->getReferenceRepository()->setReference($reference, $object);
            }

            $em->clear();
        }

        // Restore previous taxation state after load fixtures
        $this->configManager->set('orob2b_tax.tax_enable', $previousTaxEnableState);
    }

    /**
     * @param object $object
     * @param array $item
     * @return object
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function fillData($object, array $item)
    {
        foreach ($item as $property => $config) {
            $value = $this->extractValues($config);
            $isArray = is_array($config);

            $hasPropertyValue = $isArray && array_key_exists('property_value', $config);
            if ($hasPropertyValue && is_array($config['property_value'])) {
                $value = $this->fillData($value, $config['property_value']);
            }

            $hasProperty = $isArray && array_key_exists('property', $config);
            if ($hasProperty) {
                $value = $this->propertyAccessor->getValue($value, $config['property']);
            }

            if ($isArray && !$hasPropertyValue && !$hasProperty && $this->isNestedArray($value)) {
                foreach ($value as $key => $valueElem) {
                    $this->fillData($object, [$property . '.' . $key => $valueElem]);
                }
            } else {
                $propertyPath = $this->getPropertyPath($object, $property);
                $this->propertyAccessor->setValue($object, $propertyPath, $value);
            }
        }

        return $object;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function isNestedArray($data)
    {
        if (!is_array($data)) {
            return false;
        }

        $filtered = array_filter(
            $data,
            function ($item) {
                return is_array($item);
            }
        );

        return count(array_filter($filtered)) === count($data);
    }

    /**
     * @param mixed $value
     * @param string $path
     * @return string
     */
    private function getPropertyPath($value, $path)
    {
        if (is_array($value) || $value instanceof \ArrayAccess) {
            $parts = explode('.', $path);
            $parts = array_map(
                function ($item) {
                    return sprintf('[%s]', $item);
                },
                $parts
            );

            return implode($parts);
        }

        return (string)$path;
    }

    /**
     * @param array|string $config
     * @return mixed
     */
    private function extractValues($config)
    {
        if (!is_array($config)) {
            return $config;
        }

        $hasClass = array_key_exists('class', $config);
        if ($hasClass && array_key_exists('query', $config)) {
            return $this->doctrine
                ->getRepository($config['class'])
                ->findOneBy($config['query']);
        } elseif ($hasClass) {
            return new $config['class']();
        } elseif (array_key_exists('reference', $config)) {
            return $this->getReference($config['reference']);
        } elseif (array_key_exists('property_value', $config)) {
            return $config['property_value'];
        }

        return $config;
    }

    /**
     * @param array $databaseAfter
     */
    protected function assertDatabase(array $databaseAfter)
    {
        foreach ($databaseAfter as $class => $items) {
            $repository = $this->doctrine->getRepository($class);

            foreach ($items as $item) {
                foreach ($item as $key => $param) {
                    if (is_array($param) && array_key_exists('reference', $param)) {
                        $item[$key] = $this->getReference($param['reference'])->getId();
                    }
                }

                $this->assertNotEmpty($repository->findBy($item), sprintf('%s %s', $class, json_encode($item)));
            }
        }
    }
}
