<?php

namespace Oro\Bundle\DPDBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Method\Identifier\DPDMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Provider\ChannelType;
use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeRemovalEventDispatcherInterface;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DPDTransportEntityListener
{
    /**
     * @var IntegrationMethodIdentifierGeneratorInterface
     */
    private $methodIdentifierGenerator;

    /**
     * @var DPDMethodTypeIdentifierGeneratorInterface
     */
    private $typeIdentifierGenerator;

    /**
     * @var MethodTypeRemovalEventDispatcherInterface
     */
    private $typeRemovalEventDispatcher;

    /**
     * @param IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator
     * @param DPDMethodTypeIdentifierGeneratorInterface     $typeIdentifierGenerator
     * @param MethodTypeRemovalEventDispatcherInterface     $typeRemovalEventDispatcher
     */
    public function __construct(
        IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator,
        DPDMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator,
        MethodTypeRemovalEventDispatcherInterface $typeRemovalEventDispatcher
    ) {
        $this->methodIdentifierGenerator = $methodIdentifierGenerator;
        $this->typeIdentifierGenerator = $typeIdentifierGenerator;
        $this->typeRemovalEventDispatcher = $typeRemovalEventDispatcher;
    }

    /**
     * @param DPDTransport      $transport
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
                ++$rowCounter;
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
                    $rate->setWeightValue((float) $weightValue);
                }
                $rate->setPriceValue((float) $priceValue);
                $transport->addRate($rate);
            }
            fclose($handle);
        }
    }

    /**
     * @param DPDTransport       $transport
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(DPDTransport $transport, LifecycleEventArgs $args)
    {
        /** @var PersistentCollection $services */
        $services = $transport->getApplicableShippingServices();
        $deletedServices = $services->getDeleteDiff();
        if (0 !== count($deletedServices)) {
            $entityManager = $args->getEntityManager();
            $channel = $entityManager
                ->getRepository('OroIntegrationBundle:Channel')
                ->findOneBy(['type' => ChannelType::TYPE, 'transport' => $transport->getId()]);

            if (null !== $channel) {
                foreach ($deletedServices as $deletedService) {
                    $methodId = $this->methodIdentifierGenerator->generateIdentifier($channel);
                    $typeId = $this->typeIdentifierGenerator->generateIdentifier($channel, $deletedService);
                    $this->typeRemovalEventDispatcher->dispatch($methodId, $typeId);
                }
            }
        }
    }
}
