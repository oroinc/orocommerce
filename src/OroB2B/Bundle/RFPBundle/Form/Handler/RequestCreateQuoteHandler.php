<?php

namespace OroB2B\Bundle\RFPBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;

class RequestCreateQuoteHandler
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
     * @var User
     */
    protected $user;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var DBALException
     */
    protected $exception;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     * @param User $user
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $manager, User $user)
    {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->user = $user;
    }

    /**
     * @return Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @return DBALException
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param RFPRequest $request
     * @return boolean
     */
    public function process(RFPRequest $request)
    {
        $this->form->setData($request);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccess($request);
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param RFPRequest $entity
     * @return bool
     */
    protected function onSuccess(RFPRequest $entity)
    {
        $quote = new Quote();
        $quote
            ->setRequest($entity)
            ->setOwner($this->user)
        ;

        foreach ($entity->getRequestProducts() as $requestProduct) {
            $quoteProduct = new QuoteProduct();
            $quoteProduct
                ->setProduct($requestProduct->getProduct())
                ->setType(QuoteProduct::TYPE_REQUESTED)
                ->setCommentCustomer($requestProduct->getComment())
            ;
            foreach ($requestProduct->getRequestProductItems() as $requestProductItem) {
                $quoteProductRequest = new QuoteProductRequest();
                $quoteProductRequest
                    ->setQuantity($requestProductItem->getQuantity())
                    ->setPrice($requestProductItem->getPrice())
                    ->setProductUnit($requestProductItem->getProductUnit())
                    ->setRequestProductItem($requestProductItem)
                ;
                $quoteProduct->addQuoteProductRequest($quoteProductRequest);
            }
            $quote->addQuoteProduct($quoteProduct);
        }

        try {
            $this->manager->persist($quote);
            $this->manager->flush();

            $this->quote = $quote;
        } catch (DBALException $e) {
            $this->exception = $e;

            return false;
        }

        return true;
    }
}
