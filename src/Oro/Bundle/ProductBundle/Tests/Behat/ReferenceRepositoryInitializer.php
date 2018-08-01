<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
    {
        $this->initDefaultAttributeFamily($doctrine, $referenceRepository);
        $this->initProductUnits($doctrine, $referenceRepository);
        $this->initProductUnitTranslations($doctrine, $referenceRepository);
    }

    /**
     * @param Registry $doctrine
     * @param AliceCollection $referenceRepository
     */
    private function initDefaultAttributeFamily(Registry $doctrine, AliceCollection $referenceRepository): void
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

    /**
     * @param Registry $doctrine
     * @param AliceCollection $referenceRepository
     */
    private function initProductUnits(Registry $doctrine, AliceCollection $referenceRepository): void
    {
        /** @var ProductUnitRepository $repository */
        $repository = $doctrine->getManager()->getRepository(ProductUnit::class);
        /** @var ProductUnit $item */
        $item = $repository->findOneBy(['code' => 'item']);
        $referenceRepository->set('item', $item);
        /** @var ProductUnit $each */
        $each = $repository->findOneBy(['code' => 'each']);
        $referenceRepository->set('each', $each);
        /** @var ProductUnit $set */
        $set = $repository->findOneBy(['code' => 'set']);
        $referenceRepository->set('set', $set);
        /** @var ProductUnit $piece */
        $piece = $repository->findOneBy(['code' => 'piece']);
        $referenceRepository->set('piece', $piece);
    }

    /**
     * Adds to references for TranslationKey objects for product units.
     * Format: translation_key_%domain%_%translation_key_dots_replaced_by_underscores%
     * Example: translation_key_jsmessages_oro_product_product_unit_bottle_value_label
     *
     * @param Registry $doctrine
     * @param AliceCollection $referenceRepository
     */
    private function initProductUnitTranslations(Registry $doctrine, AliceCollection $referenceRepository): void
    {
        /** @var TranslationKeyRepository $repository */
        $repository = $doctrine->getManager()->getRepository('OroTranslationBundle:TranslationKey');
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
}
