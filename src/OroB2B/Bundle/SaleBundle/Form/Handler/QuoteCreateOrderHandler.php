<?php

namespace OroB2B\Bundle\SaleBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderProduct;
use OroB2B\Bundle\OrderBundle\Entity\OrderProductItem;
use OroB2B\Bundle\SaleBundle\Entity\Quote;

class QuoteCreateOrderHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var AccountUser
     */
    protected $user;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var DBALException
     */
    protected $exception;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     * @param AccountUser $user
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $manager, AccountUser $user)
    {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->user = $user;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return DBALException
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param Quote $quote
     * @return boolean
     */
    public function process(Quote $quote)
    {
        $this->form->setData($quote);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccess($quote);
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param Quote $entity
     * @return bool
     */
    protected function onSuccess(Quote $entity)
    {
        $order = new Order();
        $order
            ->setOwner($entity->getOwner())
            ->setQuote($entity)
            ->setAccountUser($this->user)
            ->setAccount($this->user->getCustomer())
        ;

        foreach ($entity->getQuoteProducts() as $quoteProduct) {
            $orderProduct = new OrderProduct();
            $orderProduct
                ->setProduct($quoteProduct->getProduct())
                ->setComment($quoteProduct->getComment())
            ;
            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                $orderProductItem = new OrderProductItem();
                $orderProductItem
                    ->setFromQuote(true)
                    ->setQuantity($quoteProductOffer->getQuantity())
                    ->setPrice($quoteProductOffer->getPrice())
                    ->setProductUnit($quoteProductOffer->getProductUnit())
                    ->setQuoteProductOffer($quoteProductOffer)
                    ->setPriceType($quoteProductOffer->getPriceType())
                ;
                $orderProduct->addOrderProductItem($orderProductItem);
            }
            $order->addOrderProduct($orderProduct);
        }

        try {
            $this->manager->persist($order);
            $this->manager->flush();

            $this->order = $order;
        } catch (DBALException $e) {
            $this->exception = $e;

            return false;
        }

        return true;
    }
}
