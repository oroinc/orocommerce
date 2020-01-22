<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\TemplateFixture;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Fixture of Category entity used for generation of import-export template
 */
class CategoryFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    /** @var RegistryInterface */
    private $doctrine;

    /** @var LocalizationManager */
    private $localizationManager;

    /**
     * @param RegistryInterface $doctrine
     */
    public function setDoctrine(RegistryInterface $doctrine): void
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param LocalizationManager $localizationManager
     */
    public function setLocalizationManager(LocalizationManager $localizationManager)
    {
        $this->localizationManager = $localizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return Category::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->getEntityData('Sample Category');
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity($key)
    {
        return new Category();
    }

    /**
     * @param string $key
     * @param Category $entity
     */
    public function fillEntityData($key, $entity)
    {
        if ($key === 'Sample Category') {
            $rootCategory = $this->doctrine
                ->getEntityManagerForClass(Category::class)
                ->getRepository(Category::class)
                ->getMasterCatalogRoot();
            $localization = $this->localizationManager->getDefaultLocalization();
            $entity
                ->setParentCategory($rootCategory)
                ->addTitle((new LocalizedFallbackValue())->setString('Sample Category'))
                ->addTitle(
                    (new LocalizedFallbackValue())
                        ->setString('Sample Category English')
                        ->setLocalization($localization)
                )
                ->addShortDescription((new LocalizedFallbackValue())->setText('Sample short description'))
                ->addLongDescription((new LocalizedFallbackValue())->setText('Sample long description'));

            return;
        }

        parent::fillEntityData($key, $entity);
    }
}
