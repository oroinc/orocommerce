<?php

namespace Oro\Bundle\SaleBundle\Form;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPriceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class QuoteFormTemplateDataProvider implements FormTemplateDataProviderInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var QuoteProductPriceProvider
     */
    private $quoteProductPriceProvider;

    /**
     * @var QuoteAddressSecurityProvider
     */
    private $quoteAddressSecurityProvider;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        QuoteProductPriceProvider $quoteProductPriceProvider,
        QuoteAddressSecurityProvider $quoteAddressSecurityProvider
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->quoteProductPriceProvider = $quoteProductPriceProvider;
        $this->quoteAddressSecurityProvider = $quoteAddressSecurityProvider;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function getData($entity, FormInterface $form, Request $request)
    {
        if (!$entity instanceof Quote) {
            throw new \InvalidArgumentException(
                sprintf('`%s` supports only `%s` instance as form data (entity).', self::class, Quote::class)
            );
        }

        $submittedData = $request->get($form->getName());
        $event = new QuoteEvent($form, $form->getData(), $submittedData);
        $this->eventDispatcher->dispatch($event, QuoteEvent::NAME);
        $quoteData = $event->getData()->getArrayCopy();

        return [
            'entity' => $entity,
            'form' => $form->createView(),
            'tierPrices' => $this->quoteProductPriceProvider->getTierPrices($entity),
            'matchedPrices' => $this->quoteProductPriceProvider->getMatchedPrices($entity),
            'isShippingAddressGranted' => $this->quoteAddressSecurityProvider
                ->isAddressGranted($entity, AddressType::TYPE_SHIPPING),
            'quoteData' => $quoteData
        ];
    }
}
