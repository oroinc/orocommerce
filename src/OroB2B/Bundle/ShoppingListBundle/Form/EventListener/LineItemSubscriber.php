<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\EventListener;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Manager\LineItemManager;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class LineItemSubscriber implements EventSubscriberInterface
{
    /**
     * @var LineItemManager
     */
    protected $lineItemManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $productClass;

    /**
     * @param LineItemManager $lineItemManager
     * @param ManagerRegistry $registry
     */
    public function __construct(LineItemManager $lineItemManager, ManagerRegistry $registry)
    {
        $this->lineItemManager = $lineItemManager;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit'
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        /** @var LineItem $lineItem */
        $lineItem = $event->getForm()->getData();
        $data = $event->getData();

        $product = empty($data['product']) ? $lineItem->getProduct() : (int)$data['product'];
        if (!$product instanceof Product && !empty($product)) {
            /** @var ProductRepository $repository */
            $repository = $this->registry->getManagerForClass($this->productClass)->getRepository($this->productClass);

            /** @var Product $product */
            $product = $repository->find($product);
        }

        if (!$product || empty($data['unit']) || empty($data['quantity'])) {
            return;
        }

        $roundedQuantity = $this->lineItemManager->roundProductQuantity(
            $product,
            $data['unit'],
            $data['quantity']
        );
        $data['quantity'] = $roundedQuantity;
        $event->setData($data);
    }

    /**
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }
}
