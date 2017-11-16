<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
    {
        $repository = $doctrine->getManager()->getRepository(AttributeFamily::class);
        $attributeFamily = $repository->findOneBy([
            'code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE,
        ]);

        if (!$attributeFamily) {
            throw new \InvalidArgumentException('Default product attribute family should exist.');
        }

        $referenceRepository->set('defaultProductFamily', $attributeFamily);

        /** @var ProductUnitRepository $repository */
        $repository = $doctrine->getManager()->getRepository('OroProductBundle:ProductUnit');
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
}
