<?php

namespace Oro\Bundle\PricingBundle\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProductFormExtension extends AbstractTypeExtension
{
    /**
     * @var PriceManager
     */
    protected $priceManager;

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     * @param ShardManager $shardManager
     * @param PriceManager $priceManager
     */
    public function __construct(ManagerRegistry $registry, ShardManager $shardManager, PriceManager $priceManager)
    {
        $this->registry = $registry;
        $this->shardManager = $shardManager;
        $this->priceManager = $priceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Product $product */
        $product = $builder->getData();

        $builder->add(
            'prices',
            ProductPriceCollectionType::NAME,
            [
                'label' => 'oro.pricing.productprice.entity_plural_label',
                'required' => false,
                'mapped' => false,
                'constraints' => [new UniqueProductPrices()],
                'options' => [
                    'product' => $product,
                ],
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var Product|null $product */
        $product = $event->getData();
        if (!$product || !$product->getId()) {
            return;
        }

        $prices = $this->getProductPriceRepository()->getPricesByProduct($this->shardManager, $product);

        $event->getForm()->get('prices')->setData($prices);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var Product|null $product */
        $product = $event->getData();
        if (!$product) {
            return;
        }

        $form = $event->getForm();
        /** @var ProductPrice[] $prices */
        $prices = (array)$form->get('prices')->getData();
        foreach ($prices as $price) {
            $price->setProduct($product);
        }

        if (!$form->isValid()) {
            return;
        }

        $repository = $this->getProductPriceRepository();
        // persist existing prices
        $persistedPriceIds = [];
        $em = $this->getManager();
        $unitOfWork = $em->getUnitOfWork();
        $classMetadata = $em->getClassMetadata(ProductPrice::class);

        foreach ($prices as $price) {
            $priceId = $price->getId();
            if ($priceId) {
                $unitOfWork->computeChangeSet($classMetadata, $price);
                $changeSet = $unitOfWork->getEntityChangeSet($price);
                //should be moved to another shard
                if (isset($changeSet['priceList'])) {
                    $this->priceManager->remove($price);
                    $price->setId(null);
                } else {
                    $persistedPriceIds[] = $priceId;
                }
            }

            $price->setProduct($product);
            $this->priceManager->persist($price);
        }

        // remove deleted prices
        if ($product->getId()) {
            $existingPrices = $repository->getPricesByProduct($this->shardManager, $product);
            foreach ($existingPrices as $price) {
                if (!in_array($price->getId(), $persistedPriceIds, true)) {
                    $this->priceManager->remove($price);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::NAME;
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getProductPriceRepository()
    {
        return $this->getManager()
            ->getRepository('OroPricingBundle:ProductPrice');
    }

    /**
     * @return EntityManager
     */
    protected function getManager()
    {
        return $this->registry->getManagerForClass('OroPricingBundle:ProductPrice');
    }
}
