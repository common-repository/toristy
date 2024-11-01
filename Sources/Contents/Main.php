<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Contents;


class Main
{
    use JsonClass;

    public $linesOfBusiness;

    public $serviceTypes;

    public $services;

    public $service;

    public $serviceproviders;

    public $serviceprovider;

    public $locations;

    public $requesttimings;

    public $apikeydata;

    public $requestid;

    public $nextPage = false;

    public $requestparams;

    public function __construct($content)
    {
        $this->Maps = [
            'linesOfBusiness' => Business::class,
            'serviceTypes' => ServiceType::class,
            'locations' => Location::class,
            'service' => Service::class,
            'services' => Service::class,
            'serviceProvider' => Provider::class,
            'serviceProviders' => Provider::class
        ];
        $this->Deserialize($content);
    }
}