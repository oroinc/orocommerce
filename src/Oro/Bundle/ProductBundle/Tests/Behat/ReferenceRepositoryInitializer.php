<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritDoc}
     */
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $this->setDefaultProductFamilyReference($doctrine, $referenceRepository);
        $this->setProductUnitReferences($doctrine, $referenceRepository);
        $this->setProductUnitTranslationKeysReferences($doctrine, $referenceRepository);
        $this->setProductAttributesReferences($doctrine, $referenceRepository);

        $fieldConfigModelRepository = $doctrine->getManagerForClass(FieldConfigModel::class)
            ->getRepository(FieldConfigModel::class);
        $attributes = $fieldConfigModelRepository->getAttributesByClassAndIsSystem(Product::class, true);
        foreach ($attributes as $attribute) {
            $referenceRepository->set('attribute_' . $attribute->getFieldName(), $attribute);
        }
    }

    private function setDefaultProductFamilyReference(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $repository = $doctrine->getManager()->getRepository(AttributeFamily::class);
        $attributeFamily = $repository->findOneBy([
            'code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE,
        ]);
        if (!$attributeFamily) {
            throw new \InvalidArgumentException('Default product attribute family should exist.');
        }

        $referenceRepository->set('defaultProductFamily', $attributeFamily);
    }

    private function setProductUnitReferences(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        /** @var ProductUnitRepository $repository */
        $repository = $doctrine->getManager()->getRepository(ProductUnit::class);
        $referenceRepository->set('item', $repository->findOneBy(['code' => 'item']));
        $referenceRepository->set('each', $repository->findOneBy(['code' => 'each']));
        $referenceRepository->set('set', $repository->findOneBy(['code' => 'set']));
        $referenceRepository->set('piece', $repository->findOneBy(['code' => 'piece']));
        $referenceRepository->set('kg_unit', $repository->findOneBy(['code' => 'kg']));
    }

    /**
     * Adds to references for TranslationKey objects for product units.
     * Format: translation_key_%domain%_%translation_key_dots_replaced_by_underscores%
     * Example: translation_key_jsmessages_oro_product_product_unit_bottle_value_label
     */
    private function setProductUnitTranslationKeysReferences(
        ManagerRegistry $doctrine,
        Collection $referenceRepository
    ): void {
        /** @var TranslationKeyRepository $repository */
        $repository = $doctrine->getManager()->getRepository(TranslationKey::class);
        $qb = $repository->createQueryBuilder('tk');
        $qb->orWhere($qb->expr()->like('tk.key', $qb->expr()->literal('oro.product_unit.%')))
            ->orWhere($qb->expr()->like('tk.key', $qb->expr()->literal('oro.product.product_unit.%')));
        $translationKeys = $qb->getQuery()->execute();

        /** @var TranslationKey $translationKey */
        foreach ($translationKeys as $translationKey) {
            $referenceKey = sprintf(
                'translation_key_%s_%s',
                $translationKey->getDomain(),
                str_replace('.', '_', $translationKey->getKey())
            );
            $referenceRepository->set($referenceKey, $translationKey);
        }
    }

    private function setProductAttributesReferences(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $repository = $doctrine->getManagerForClass(FieldConfigModel::class)->getRepository(FieldConfigModel::class);

        foreach ($repository->getAttributesByClass(Product::class) as $attribute) {
            $referenceRepository->set('product_attribute_' . $attribute->getFieldName(), $attribute);
        }
    }
}
