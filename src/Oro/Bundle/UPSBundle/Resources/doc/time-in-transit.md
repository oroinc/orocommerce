# Time in Transit

The Time in Transit API is used to determine the time required to ship a particular package. OroUPSBundle
provides the `oro_ups.provider.time_in_transit` service that uses Time in Transit UPS API.

## How to use

To estimate transit time, call the `getTimeInTransitResult()` method of the `oro_ups.provider.time_in_transit` service
and provide the following arguments:
* `UPSTransport $transport` - transport settings entity of UPS integration.
* `AddressInterface $shipFromAddress` - origin address.
* `AddressInterface $shipToAddress` - destination address.
* `\DateTime $pickupDate` - the anticipated shipping date of the package, i.e. when the user requests UPS to pick up the
package from the origin.
* `int $weight` - weight in the unit of weight provided by the UPSTransport.

The following example snippet can be used to test the UPS integration if at least one integration is set up:

```php
    $doctrineHelper = $this->container->get('oro_entity.doctrine_helper');

    $countryRepository = $doctrineHelper->getEntityRepositoryForClass(Country::class);
    $country = $countryRepository->findOneBy(['iso2Code' => 'US']);

    $upsChannels = $doctrineHelper->getEntityRepositoryForClass(Channel::class)
        ->findBy(['type' => $this->container->getParameter('oro_ups.integration.channel.type')]);
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
* As UPS has plenty of services for shipping, and each can take a different time to ship a package so in response you
receive all available UPS services, estimated arrivals dates, and an indication of the number of business days in
transit.
* If UPS does not recognize specified addresses, the Time In Transit API responds with a list of candidate addresses
that appear to be close matches to the original address.
* Time in Transit requests are cached by `oro_ups.provider.cacheable_time_in_transit` decorator based on the origin
address, the destination address and the pickup date with the lifetime of 86400 seconds.
* Only the following properties of the origin and the destination addresses are taken into account: country, region,
city, postal code.
* The pickup date should be specified in the time zone specific for the origin address. The estimated arrival date is
provided in the timezone of the destination address.
