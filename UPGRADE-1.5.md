UPGRADE FROM 1.4 to 1.5
=======================

**IMPORTANT**
-------------

Full product reindexation has to be performed after upgrade!

ProductBundle
-------------
- Class `Oro\Bundle\ProductBundle\EventListener\WebsiteSearchProductIndexerListener`:
    - replaced dependency from `Symfony\Bridge\Doctrine\RegistryInterface` to `Doctrine\Common\Persistence\ManagerRegistry`
    - changes in constructor:
        - third argument `RegistryInterface $registry` changed to `ManagerRegistry $registry`
        - added fifth argument `Oro\Bundle\EntityConfigBundle\Manager\AttributeManager $attributeManager`
        - added sixth argument `Oro\Bundle\ProductBundle\Search\WebsiteSearchProductIndexDataProvider $dataProvider`
- Class `Oro\Bundle\ProductBundle\Form\Extension\AttributeConfigExtension`:
    - added constructor with two arguments:
        - `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider $attributeConfigProvider`
        - `Symfony\Component\Translation\TranslatorInterface $translator`
- Updated website search configuration file `Oro/Bundle/ProductBundle/Resources/config/oro/website_search.yml`:
    - removed configuration for next fields:
        - `name_LOCALIZATION_ID`
        - `sku`
        - `new_arrival`
        - `short_description_LOCALIZATION_ID`
        - `inventory_status`
    - all of this fields will be added to website search index as configuration for related product attributes
    - now in website search index some fields have new names:
        - `name_LOCALIZATION_ID` => `names_LOCALIZATION_ID`
        - `new_arrival` => `newArrival`
        - `short_description_LOCALIZATION_ID` => `shortDescriptions_LOCALIZATION_ID`

WebsiteSearchBundle
-------------------
- Class `Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider`
    - changes in constructor:
        - added fifth argument `Oro\Bundle\WebsiteSearchBundle\Helper\PlaceholderHelper $placeholderHelper`
- Entity `Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal`:
    - changed decimal field `value`:
        - `precision` changed from `10` to `21`.
        - `scale` changed from `2` to `6`.
- Class `Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider`:
    - changes in constructor:
        - added second argument `Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher`
- Added interface `Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchableAttributeTypeInterface` that should be implemented in case new type of arguments added.
- Implementation can decorate original implementation of interface `Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface` that as service with tag `oro_entity_config.attribute_type`.

PromotionBundle
---------------
- Class `Oro\Bundle\PromotionBundle\Handler\CouponValidationHandler`
    - now extends from `Oro\Bundle\PromotionBundle\Handler\AbstractCouponHandler`
    - changes in constructor:
        - dependency on `Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService` moved to `setCouponApplicabilityValidationService` setter
