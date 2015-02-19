<?php

namespace OroB2B\Bundle\AttributeBundle\Form\DataTransformer\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class DatabaseTransformerHelper
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param int $localeId
     * @return Locale|null
     */
    public function findLocale($localeId)
    {
        return $this->managerRegistry->getRepository('OroB2BWebsiteBundle:Locale')->find($localeId);
    }

    /**
     * @param int $websiteId
     * @return Website|null
     */
    public function findWebsite($websiteId)
    {
        return $this->managerRegistry->getRepository('OroB2BWebsiteBundle:Website')->find($websiteId);
    }
}
