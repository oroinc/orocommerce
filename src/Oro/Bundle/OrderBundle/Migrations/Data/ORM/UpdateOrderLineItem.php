<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Refreshes product names for all OrderLineItem
 */
class UpdateOrderLineItem extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const BATCH_SIZE = 200;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->isApplicable()) {
            return;
        }

        /** @var EntityManager $manager */
        $manager->getConnection()
            ->executeQuery(
                'UPDATE oro_order_line_item AS li
                SET product_name = COALESCE (
                    (SELECT name FROM oro_product WHERE id = li.parent_product_id),
                    (SELECT name FROM oro_product WHERE id = li.product_id)
                );'
            );

        $provider = $this->container->get('oro_product.layout.data_provider.configurable_products');

        $qb = $manager->getRepository(OrderLineItem::class)->createQueryBuilder('li');
        $qb->where($qb->expr()->gt('li.id', ':fromId'))
            ->innerJoin('li.parentProduct', 'p')
            ->orderBy('li.id', 'ASC')
            ->setMaxResults(self::BATCH_SIZE)
            ->getQuery();

        $query = $qb->getQuery();
        $id = 0;

        /** @var OrderLineItem[] $lineItems */
        while ($lineItems = $query->execute(['fromId' => $id])) {
            foreach ($lineItems as $lineItem) {
                $id = $lineItem->getId();

                $product = $lineItem->getProduct();
                if ($product) {
                    $fields = $provider->getVariantFieldsValuesForLineItem($lineItem, false);
                    if (!empty($fields[$product->getId()])) {
                        $lineItem->setProductVariantFields($fields[$product->getId()]);
                    }
                }
            }

            $manager->flush();
            $manager->clear();
        }
    }

    /**
     * @return bool
     */
    private function isApplicable()
    {
        return $this->container->get(ApplicationState::class)->isInstalled();
    }
}
