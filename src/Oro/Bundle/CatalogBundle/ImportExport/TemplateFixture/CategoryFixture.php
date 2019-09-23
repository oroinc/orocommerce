<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\TemplateFixture;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Fixture of Category entity used for generation of import-export template
 */
class CategoryFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    /** @var RegistryInterface */
    private $doctrine;

    /**
     * @param RegistryInterface $doctrine
     */
    public function setDoctrine(RegistryInterface $doctrine): void
    {
        $this->doctrine = $doctrine;
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
            $localization = new Localization();
            $localization->setName('English');
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
