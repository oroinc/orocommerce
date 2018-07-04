# Time in Transit

The Time in Transit API is used to determine the length of time required to ship a particular package. OroUPSBundle
provides the service `oro_ups.provider.time_in_transit` which makes use of Time in Transit UPS API.

## How to use

In order to get estimated transit time, you should call `getTimeInTransitResult()` method of service
`oro_ups.provider.time_in_transit` and provide the following arguments:
* `UPSTransport $transport` - transport settings entity of UPS integration.
* `AddressInterface $shipFromAddress` - origin address.
* `AddressInterface $shipToAddress` - destination address.
* `\DateTime $pickupDate` - anticipated shipping date of package, i.e. the date the user requests UPS to pickup the
package from the origin.
* `int $weight` - weight in unit of weight which is specified in provided UPSTransport.

Example snippet, assumes that you have at least one UPS integration:

```php
        $doctrineHelper = $this->container->get('oro_entity.doctrine_helper');

        $countryRepository = $doctrineHelper->getEntityRepositoryForClass(Country::class);
        $country = $countryRepository->findOneBy(['iso2Code' => 'US']);

        $channelRepository = $doctrineHelper->getEntityRepositoryForClass(Channel::class);
        $upsChannels = $channelRepository->findByType($this->container->getParameter('oro_ups.integration.channel.type'));
        $upsChannel = current($upsChannels);

        /** @var UPSTransport $transport */
        $transport = $upsChannel->getTransport();
        $shipFromAddress = (new Address())->setPostalCode('90046')->setCountry($country);
        $shipToAddress = (new Address())->setPostalCode('66062')->setCountry($country);
        $pickupDate = new \DateTime('+3 days');
        $weight = 1;

        $timeInTransitProvider = $this->container->get('oro_ups.provider.time_in_transit');

        /** @var TimeInTransitResult $result */
        $result = $timeInTransitProvider
            ->getTimeInTransitResult($transport, $shipFromAddress, $shipToAddress, $pickupDate, $weight);
        if ($result->getStatus()) {
            $estimatedArrivals = $result->getEstimatedArrivals();
            foreach ($estimatedArrivals as $upsServiceCode => $estimatedArrival) {
                printf(
                    'UPS Service Code: %s, Estimated arrival date: %s',
                    $upsServiceCode,
                    $estimatedArrival->getArrivalDate()->format('c')
                );
            }
        }
```

## Things to Consider
* As UPS has plenty of services for shipping, and each can take different time to ship a package, in response you will
have all available UPS services, estimated arrivals dates and an indication of the business days in transit.
* If UPS does not recognize specified addresses, the Time In Transit API responds with a list of candidate addresses
that appear to be close matches to the original address.
* Time in Transit requests are cached by `oro_ups.provider.cacheable_time_in_transit` decorator based on origin address,
destination address and pickup date with a lifetime of 86400 seconds.
* Only the following properties of origin and destination addresses are taken into account: country, region, city,
postal code.
* Pickup date should be specified in the timezone specific for origin address. Estimated arrival date will be provided
in the timezone of destination address.
