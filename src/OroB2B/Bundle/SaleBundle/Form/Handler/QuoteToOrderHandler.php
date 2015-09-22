<?php

namespace OroB2B\Bundle\SaleBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Model\QuoteToOrderConverter;

class QuoteToOrderHandler
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
     * @var QuoteToOrderConverter
     */
    protected $converter;

    /**
     * @var AccountUser
     */
    protected $user;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     * @param QuoteToOrderConverter $converter
     * @param AccountUser $user
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        QuoteToOrderConverter $converter,
        AccountUser $user
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->converter = $converter;
        $this->user = $user;
    }

    /**
     * @param Quote $quote
     * @return Order|null
     */
    public function process(Quote $quote)
    {
        $this->form->setData($quote);

        if ($this->request->getMethod() === 'POST') {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccess($quote, $this->form->getData());
            }
        }

        return null;
    }

    /**
     * @param Quote $quote
     * @param array $offerData
     * @return Order|null
     */
    protected function onSuccess(Quote $quote, array $offerData)
    {
        $order = $this->converter->convert($quote, $this->user, $offerData);

        $this->manager->persist($order);
        $this->manager->flush();

        return $order;
    }
}
