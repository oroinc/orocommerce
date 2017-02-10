<?php

namespace Oro\Bundle\SaleBundle\Event;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\ShippingBundle\EventListener\EntityDataAwareEventInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;

class QuoteEvent extends Event implements EntityDataAwareEventInterface
{
    const NAME = 'oro_sale.quote';

    /** @var FormInterface */
    protected $form;

    /** @var Quote */
    protected $quote;

    /** @var \ArrayObject */
    protected $data;

    /** @var array */
    protected $submittedData = [];

    /**
     * @param FormInterface $form
     * @param Quote         $quote
     * @param array|null    $submittedData
     */
    public function __construct(FormInterface $form, Quote $quote, array $submittedData = null)
    {
        $this->form = $form;
        $this->quote = $quote;
        $this->submittedData = $submittedData;
        $this->data = new \ArrayObject();
    }

    /**
     * @return Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @return \ArrayObject
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubmittedData()
    {
        return $this->submittedData;
    }

    /**
     * @return Quote
     */
    public function getEntity()
    {
        return $this->getQuote();
    }
}
