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
     * {@inheritDoc}
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
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'], 10);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var Product|null $product */
        $product = $form->getData();
        if (!$product) {
            return;
        }

        $submittedData = $event->getData();

        if (array_key_exists('prices', $submittedData)) {
            $submittedPrices = $submittedData['prices'];

            if ($product->getId()) {
                $replacedPrices = [];
                $existingPrices = $this->getProductPriceRepository()->getPricesByProduct($this->shardManager, $product);
                foreach ($submittedPrices as $key => $submittedPrice) {
                    foreach ($existingPrices as $k => $existingPrice) {
                        if ($key !== $k && $this->assertUniqueAttributes($submittedPrice, $existingPrice)) {
                            $replacedPrices[$k] = $submittedPrice;
                            break;
                        }
                    }
                }
                $correctPrices = array_replace($submittedPrices, $replacedPrices);
                $submittedData['prices'] = $correctPrices;
                $event->setData($submittedData);
            }
        }
    }

    /**
     * {@inheritDoc}
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

        foreach ($prices as $price) {
            $priceId = $price->getId();
            if ($priceId) {
                $persistedPriceIds[] = $priceId;
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

    /**
     * @param array        $submitted
     * @param ProductPrice $existing
     *
     * @return boolean
     */
    protected function assertUniqueAttributes(array $submitted, ProductPrice $existing)
    {
        if ($submitted['priceList'] !== (string)$existing->getPriceList()->getId()) {
            return false;
        }
        if ($submitted['price']['currency'] !== $existing->getPrice()->getCurrency()) {
            return false;
        }
        if ($submitted['quantity'] !== (string)$existing->getQuantity()) {
            return false;
        }
        if ($submitted['unit'] !== $existing->getUnit()->getCode()) {
            return false;
        }

        return true;
    }
}
