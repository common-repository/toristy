<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Helpers;


use Toristy\Apis\Client;
use Toristy\Contents\Main;
use Toristy\Contents\Provider;
use Toristy\Contents\Service;
use Toristy\Cores\Option;

class Process
{
    /**
     * @var Client
     */
    private $Client;
    /**
     * @var string
     */
    private $Name;

    private $Max = 99;

    private $Min = 40;

    public function __construct(string $token, string $name)
    {
        $this->Client = (strlen($token) > 0) ? new Client($token, 0) : null;
        $this->Name = $name;
    }

    private function Get(array $params = [], array $queries = []): ?Main
    {
        $result = $this->Client->Get($params, $queries);
        if ($result->IsSuccess()) {
            $data = $result->GetData();
            if ($data !== "") {
                return new Main($data);
            }
        }
        return null;
    }

    public function Categories(int $page): int
    {
        $query = ["pagenum" => $page, "maxperpage" => $this->Max];
        $main = $this->Get(["linesOfBusiness"], $query);
        $business = [];
        if ($main instanceof Main && isset($main->linesOfBusiness)
            && is_array($main->linesOfBusiness)) {
            $business = $main->linesOfBusiness;
        }
        if (!empty($business)) {
            Option::Set($this->Name, $business, true);
        }
        return 0;
    }

    public function Types(int $page): int
    {
        $query = ["pagenum" => $page, "maxperpage" => $this->Max];
        $main = $this->Get(["serviceTypes"], $query);
        $types = [];
        if ($main instanceof Main && isset($main->serviceTypes)
            && is_array($main->serviceTypes)) {
            $types = $main->serviceTypes;
        }
        if (!empty($types)) {
            Option::Set($this->Name, $types, true);
            if (count($types) >= $this->Max) { return 1; }
        }
        return 0;
    }

    public function Locations(int $page): int
    {
        $query = ["pagenum" => $page, "maxperpage" => $this->Max];
        $main = $this->Get(["locations"], $query);
        $locations = [];
        if ($main instanceof Main && isset($main->locations)
            && is_array($main->locations)) {
            $locations = $main->locations;
        }
        if (!empty($locations)) {
            Option::Set($this->Name, $locations, true);
            //if (count($locations) >= $this->Max) { return 1; }
        }
        return 0;
    }

    public function Providers(int $page): int
    {
        $query = ["pagenum" => $page, "maxperpage" => $this->Max];
        $main = $this->Get(["serviceproviders"], $query);
        //Option::Set('toristy-debug', $main, true);
        $providers = ($main instanceof Main && isset($main->serviceproviders)
            && is_array($main->serviceproviders) && count($main->serviceproviders) > 0) ? $main->serviceproviders : [];
        if (!empty($providers)) {
            Option::Set($this->Name, $providers, true);
            if (count($providers) >= $this->Max) { return 1; }
        }
        return 0;
    }

    public function Services(int $page): int
    {
        $query = ["pagenum" => $page, "maxperpage" => $this->Max];
        $main = $this->Get(["services"], $query);
        $services = ($main instanceof Main && isset($main->services)
            && is_array($main->services) && !empty($main->services)) ? $main->services : [];
        Option::Set('toristy-debug', ['page-num' =>$page,  'count' => count($services)], true);
        if (!empty($services)) {
            Option::Set($this->Name, $services, true);
            if (count($services) >= $this->Max) { return 1; }
        }
        return 0;
    }

    public function Service(Service &$service) : string
    {
        if (!$this->Can($service->date)) { return ''; }
        $key = (string)$service->GetId();
        $main = $this->Get(["service", $key]);
        if ($main instanceof Main && isset($main->service)
           && $main->service instanceof Service) {
            $service->Merge($main->service);
            $service->date = date("Y-m-d H:i:s", strtotime('+12 hours'));
            return $key;
        }
        return '';
    }

    public function Provider(Provider &$provider) : string
    {
        if (!$this->Can($provider->date)) { return ''; }
        $key = (string)$provider->GetId();
        $main = $this->Get(["serviceprovider", $key, 'reviews']);
        if ($main instanceof Main && isset($main->serviceprovider)
            && $main->serviceprovider instanceof Provider) {
            $provider->Merge($main->serviceprovider);
            $provider->date = date("Y-m-d H:i:s", strtotime('+12 hours'));
            return $key;
        }
        return '';
    }

    private function Can(?string $date, int $hrs = 12): bool
    {
        $bol = true;
        if (isset($date) && $date !== "")
        {
            $older = strtotime($date);
            $cur = date("Y-m-d H:i:s");
            $newer = strtotime($cur);
            $hour = round(abs($newer - $older) / (60 * 60));
            $bol = $hour > $hrs;
        }
        return $bol;
    }
}