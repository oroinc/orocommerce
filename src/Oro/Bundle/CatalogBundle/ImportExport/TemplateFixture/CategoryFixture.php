<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\TemplateFixture;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryLongDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProviderInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Fixture of Category entity used for generation of import-export template
 */
class CategoryFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    /** @var MasterCatalogRootProviderInterface */
    private $masterCatalogRootProvider;

    /** @var LocalizationManager */
    private $localizationManager;

    public function __construct(LocalizationManager $localizationManager)
    {
        $this->localizationManager = $localizationManager;
    }

    public function setMasterCatalogRootProvider(MasterCatalogRootProviderInterface $masterCatalogRootProvider): void
    {
        $this->masterCatalogRootProvider = $masterCatalogRootProvider;
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
        $organizationRepo = $this->templateManager->getEntityRepository(Organization::class);

        if ($key === 'Sample Category') {
            $localization = $this->localizationManager->getDefaultLocalization();
            $entity
                ->setParentCategory($this->masterCatalogRootProvider->getMasterCatalogRoot())
                ->addTitle((new CategoryTitle())->setString('Sample Category'))
                ->addTitle(
                    (new CategoryTitle())
                        ->setString('Sample Category English')
                        ->setLocalization($localization)
                )
                ->addShortDescription((new CategoryShortDescription())->setText('Sample short description'))
                ->addLongDescription((new CategoryLongDescription())->setWysiwyg('Sample long description'))
                ->setOrganization($organizationRepo->getEntity('default'));

            return;
        }

        parent::fillEntityData($key, $entity);
    }
}
