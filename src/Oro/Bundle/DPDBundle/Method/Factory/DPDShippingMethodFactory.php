<?php

namespace Oro\Bundle\DPDBundle\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\DPDBundle\Provider\DPDTransport;

class DPDShippingMethodFactory implements IntegrationShippingMethodFactoryInterface
{
    /**
     * @var DPDTransport
     */
    private $transport;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var IntegrationMethodIdentifierGeneratorInterface
     */
    private $methodIdentifierGenerator;

    /**
     * @var DPDShippingMethodTypeFactoryInterface
     */
    private $methodTypeFactory;

    /**
     * @param DPDTransport                                  $transport
     * @param LocalizationHelper                            $localizationHelper
     * @param IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator
     * @param DPDShippingMethodTypeFactoryInterface         $methodTypeFactory
     */
    public function __construct(
        DPDTransport $transport,
        LocalizationHelper $localizationHelper,
        IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator,
        DPDShippingMethodTypeFactoryInterface $methodTypeFactory
    ) {
        $this->transport = $transport;
        $this->localizationHelper = $localizationHelper;
        $this->methodIdentifierGenerator = $methodIdentifierGenerator;
        $this->methodTypeFactory = $methodTypeFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function create(Channel $channel)
    {
        return new DPDShippingMethod(
            $this->getIdentifier($channel),
            $this->getLabel($channel),
            $this->createTypes($channel),
            $this->getSettings($channel),
            $this->transport
        );
    }

    /**
     * @param Channel $channel
     *
     * @return string
     */
    private function getIdentifier(Channel $channel)
    {
        return $this->methodIdentifierGenerator->generateIdentifier($channel);
    }

    /**
     * @param Channel $channel
     *
     * @return string
     */
    private function getLabel(Channel $channel)
    {
        $settings = $this->getSettings($channel);

        return (string) $this->localizationHelper->getLocalizedValue($settings->getLabels());
    }

    /**
     * @param Channel $channel
     *
     * @return \Oro\Bundle\IntegrationBundle\Entity\Transport|DPDSettings
     */
    private function getSettings(Channel $channel)
    {
        return $channel->getTransport();
    }

    /**
     * @param Channel $channel
     *
     * @return array
     */
    private function createTypes(Channel $channel)
    {
        $applicableShippingServices = $this->getSettings($channel)->getApplicableShippingServices()->toArray();

        return array_map(function (ShippingService $shippingService) use ($channel) {
            return $this->methodTypeFactory->create($channel, $shippingService);
        }, $applicableShippingServices);
    }
}
