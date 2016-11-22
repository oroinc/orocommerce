<?php

namespace Oro\Bundle\UPSBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\FormInterface;

class InvalidateCacheAtHandler
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var ShippingPriceCache
     */
    protected $shippingPriceCache;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param ShippingPriceCache $shippingPriceCache
     */
    public function __construct(ManagerRegistry $managerRegistry, ShippingPriceCache $shippingPriceCache)
    {
        $this->manager = $managerRegistry->getManagerForClass(UPSTransport::class);
        $this->shippingPriceCache = $shippingPriceCache;
    }

    /**
     * @param Channel $channel
     * @param FormInterface $form
     * @throws AlreadySubmittedException
     * @throws \OutOfBoundsException
     */
    public function process(Channel $channel, FormInterface $form)
    {
        if ($form->get('invalidateNow')->getData()) {
            $this->shippingPriceCache->deleteAll();
        } else {
            $datetime = $form->get('invalidateCacheAt')->getData();
            if ($datetime) {
                /** @var  UPSTransport $transport */
                $transport = $channel->getTransport();
                $transport->setInvalidateCacheAt($datetime);
                $this->manager->flush();
            }
        }
    }
}
