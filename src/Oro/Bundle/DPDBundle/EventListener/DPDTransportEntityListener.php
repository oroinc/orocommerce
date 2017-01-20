<?php

namespace Oro\Bundle\DPDBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\DPDBundle\Provider\ChannelType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DPDTransportEntityListener
{
    /**
     * @param DPDTransport $transport
     * @param PreFlushEventArgs $args
     */
    public function preFlush(DPDTransport $transport, PreFlushEventArgs $args)
    {
        if ($transport->getRatesCsv() instanceof UploadedFile) {
            $entityManager = $args->getEntityManager();

            $transport->removeAllRates();

            $handle = fopen($transport->getRatesCsv()->getRealPath(), 'rb');
            $rowCounter = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $rowCounter++;
                if ($rowCounter === 1) {
                    continue;
                }
                list($shippingServiceCode, $countryCode, $regionCode, $weightValue, $priceValue) = $row;

                $rate = new Rate();
                $rate->setShippingService($entityManager->getReference('OroDPDBundle:ShippingService',
                    $shippingServiceCode));
                $rate->setCountry($entityManager->getReference('OroAddressBundle:Country', $countryCode));
                if (!empty($regionCode)) {
                    $rate->setRegion($entityManager->getReference('OroAddressBundle:Region', $regionCode));
                }
                if (!empty($weightValue)) {
                    $rate->setWeightValue((float)$weightValue);
                }
                $rate->setPriceValue((float)$priceValue);
                $transport->addRate($rate);
            }
            fclose($handle);
        }
    }

    /**
     * @param DPDTransport $transport
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(DPDTransport $transport, LifecycleEventArgs $args)
    {
        /** @var PersistentCollection $services */
        $services = $transport->getApplicableShippingServices();
        $deletedServices = $services->getDeleteDiff();
        if (0 !== count($deletedServices)) {
            $deleted = [];
            /** @var ShippingService $deletedService */
            foreach ($deletedServices as $deletedService) {
                $deleted[] = $deletedService->getCode();
            }
            $entityManager = $args->getEntityManager();
            $channel = $entityManager
                ->getRepository('OroIntegrationBundle:Channel')
                ->findOneBy(['type' => ChannelType::TYPE, 'transport' => $transport->getId()]);

            if (null !== $channel) {
                $shippingMethodIdentifier = DPDShippingMethod::IDENTIFIER . '_' . $channel->getId();
                $configuredMethods = $entityManager
                    ->getRepository('OroShippingBundle:ShippingMethodConfig')
                    ->findBy(['method' => $shippingMethodIdentifier]);
                if (0 < count($configuredMethods)) {
                    $types = $entityManager
                        ->getRepository('OroShippingBundle:ShippingMethodTypeConfig')
                        ->findBy(['methodConfig' => $configuredMethods, 'type' => $deleted]);

                    foreach ($types as $type) {
                        $entityManager->getRepository('OroShippingBundle:ShippingMethodTypeConfig')
                            ->deleteByMethodAndType($type->getMethodConfig(), $type->getType());
                    }
                }
            }
        }
    }
}
