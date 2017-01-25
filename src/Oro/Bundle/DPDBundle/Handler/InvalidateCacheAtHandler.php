<?php

namespace Oro\Bundle\DPDBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCache;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\DPDBundle\Command\InvalidateCacheScheduleCommand;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\FormInterface;

class InvalidateCacheAtHandler
{
    const COMMAND = InvalidateCacheScheduleCommand::NAME;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var ZipCodeRulesCache
     */
    protected $zipCodeRulesCache;

    /**
     * @var ShippingPriceCache
     */
    protected $shippingPriceCache;

    /**
     * @var DeferredScheduler
     */
    protected $deferredScheduler;

    /**
     * @param ManagerRegistry    $managerRegistry
     * @param ZipCodeRulesCache  $zipCodeRulesCache
     * @param ShippingPriceCache $shippingPriceCache
     * @param DeferredScheduler  $deferredScheduler
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ZipCodeRulesCache $zipCodeRulesCache,
        ShippingPriceCache $shippingPriceCache,
        DeferredScheduler $deferredScheduler
    ) {
        $this->manager = $managerRegistry->getManagerForClass(DPDTransport::class);
        $this->zipCodeRulesCache = $zipCodeRulesCache;
        $this->shippingPriceCache = $shippingPriceCache;
        $this->deferredScheduler = $deferredScheduler;
    }

    /**
     * @param Channel       $channel
     * @param FormInterface $form
     *
     * @throws AlreadySubmittedException
     * @throws \OutOfBoundsException
     */
    public function process(Channel $channel, FormInterface $form)
    {
        if ($form->get('invalidateNow')->getData()) {
            $this->zipCodeRulesCache->deleteAll($channel->getTransport()->getId());
            $this->shippingPriceCache->deleteAllPrices();
        } else {
            $newDateTime = $form->get('invalidateCacheAt')->getData();
            /** @var DPDTransport $transport */
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
     *
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
