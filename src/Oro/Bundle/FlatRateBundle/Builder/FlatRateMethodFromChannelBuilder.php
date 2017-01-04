<?php

namespace Oro\Bundle\FlatRateBundle\Builder;

use Oro\Bundle\FlatRateBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateBundle\Method\FlatRateMethod;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

class FlatRateMethodFromChannelBuilder
{
    /** @var LocalizationHelper */
    private $localizationHelper;

    /**
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param Channel $channel
     *
     * @return FlatRateMethod
     */
    public function build(Channel $channel)
    {
        $label = $this->getChannelLabel($channel);

        return new FlatRateMethod($label);
    }

    /**
     * @param Channel $channel
     *
     * @return string
     */
    private function getChannelLabel(Channel $channel)
    {
        /** @var FlatRateSettings $transport */
        $transport = $channel->getTransport();

        return (string) $this->localizationHelper->getLocalizedValue($transport->getLabels());
    }
}
