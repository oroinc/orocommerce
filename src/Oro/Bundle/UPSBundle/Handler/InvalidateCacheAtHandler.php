<?php

namespace Oro\Bundle\UPSBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache as UPSShippingPriceCache;
use Oro\Bundle\UPSBundle\Command\InvalidateCacheScheduleCommand;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\FormInterface;

/**
 * @deprecated since 1.1.0, to be removed in 1.3.0.
 * Functionality was moved to CacheBundle with usage of Oro\Bundle\UPSBundle\Handler\InvalidateCacheActionHandler
 */
class InvalidateCacheAtHandler
{
    const COMMAND = InvalidateCacheScheduleCommand::NAME;
    
    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var UPSShippingPriceCache
     */
    protected $upsShippingPriceCache;

    /**
     * @var ShippingPriceCache
     */
    protected $shippingPriceCache;

    /**
     * @var DeferredScheduler
     */
    protected $deferredScheduler;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param UPSShippingPriceCache $upsShippingPriceCache
     * @param ShippingPriceCache $shippingPriceCache
     * @param DeferredScheduler $deferredScheduler
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        UPSShippingPriceCache $upsShippingPriceCache,
        ShippingPriceCache $shippingPriceCache,
        DeferredScheduler $deferredScheduler
    ) {
        $this->manager = $managerRegistry->getManagerForClass(UPSTransport::class);
        $this->upsShippingPriceCache = $upsShippingPriceCache;
        $this->shippingPriceCache = $shippingPriceCache;
        $this->deferredScheduler = $deferredScheduler;
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
            $this->upsShippingPriceCache->deleteAll($channel->getTransport()->getId());
            $this->shippingPriceCache->deleteAllPrices();
        } else {
            $newDateTime = $form->get('invalidateCacheAt')->getData();
            /** @var  UPSTransport $transport */
            $transport = $channel->getTransport();
            $oldDateTime = $transport->getInvalidateCacheAt();
            if ($oldDateTime != $newDateTime) {
                if ($oldDateTime) {
                    $this->deferredScheduler->removeSchedule(
                        self::COMMAND,
                        [sprintf('--id=%d', $transport->getId())],
                        $this->convertDatetimeToCron($oldDateTime)
                    );
                }
                if ($newDateTime) {
                    $this->deferredScheduler->addSchedule(
                        self::COMMAND,
                        [sprintf('--id=%d', $transport->getId())],
                        $this->convertDatetimeToCron($newDateTime)
                    );
                }
                $this->deferredScheduler->flush();
                $transport->setInvalidateCacheAt($newDateTime);
                $this->manager->flush();
            }
        }
    }

    /**
     * @param \DateTime $datetime
     * @return string
     */
    protected function convertDatetimeToCron($datetime)
    {
        $dateArray = getdate(strtotime($datetime->format('Y-m-d H:i:s')));
        return sprintf(
            '%d %d %d %d *',
            $dateArray['minutes'],
            $dateArray['hours'],
            $dateArray['mday'],
            $dateArray['mon']
        );
    }
}
