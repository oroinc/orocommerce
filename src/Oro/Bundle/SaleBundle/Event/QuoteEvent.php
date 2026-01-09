<?php

namespace Oro\Bundle\SaleBundle\Event;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\ShippingBundle\EventListener\EntityDataAwareEventInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched during quote form processing and submission.
 *
 * This event carries the quote form, the quote entity, and any submitted data,
 * allowing listeners to react to quote-related operations and modify quote data before or after form submission.
 */
class QuoteEvent extends Event implements EntityDataAwareEventInterface
{
    public const NAME = 'oro_sale.quote';

    /** @var FormInterface */
    protected $form;

    /** @var Quote */
    protected $quote;

    /** @var \ArrayObject */
    protected $data;

    /** @var array */
    protected $submittedData = [];

    public function __construct(FormInterface $form, Quote $quote, ?array $submittedData = null)
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
    #[\Override]
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

    #[\Override]
    public function getSubmittedData()
    {
        return $this->submittedData;
    }

    /**
     * @return Quote
     */
    #[\Override]
    public function getEntity()
    {
        return $this->getQuote();
    }
}
