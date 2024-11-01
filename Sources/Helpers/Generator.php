<?php
/**
 * Package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Helpers;


use Exception;
use Toristy\Contents\Business;
use Toristy\Contents\Location;
use Toristy\Contents\Provider;
use Toristy\Contents\Service;
use Toristy\Contents\ServiceType;
use Toristy\Cores\Category;
use Toristy\Cores\Option;
use Toristy\Cores\Page;
use Toristy\Cores\Plugin;
use WP_Term;

class Generator
{
    /**
     * @var Category
     */
    private $Category;
    /**
     * @var Page
     */
    private $Page;
    /**
     * @var array
     */
    private $Datas;

    private $Date;
    /**
     * @var string
     */
    private $Token;

    public function __construct(array $datas = [], string $cleanName = '')
    {
        $this->Datas = $datas;
        $this->Date = (string)Option::Get($cleanName, '');;
        $this->Category = Plugin::Get('category');
        $this->Page = Plugin::Get('page');
        $this->Token = (string)Option::Get('toristy_api_key', '', true);
    }

    public function Categories(): bool
    {
        $categories = $this->Datas;
        if (count($categories) <= 0) { return true; }
        try {
            $type = "toristy-category";
            foreach ($categories as $category) {
                if (!$category instanceof Business) {
                    continue;
                }
                $key = $category->GetId();
                $title = $category->GetName();
                if ((int)$key <= 0 || $title === "") { continue; }
                $this->Category->Update($key, $type, $title, '', $category);
            }
            return true;
        } catch (Exception $e) {}
        return false;
    }

    public function Types(): bool
    {
        $subs = $this->Datas;
        if (count($subs) <= 0) { return true; }
        try {
            $type = "toristy-type";
            foreach ($subs as $sub) {
                if (!$sub instanceof ServiceType) { continue; }
                $title = $sub->GetName();
                $key = $sub->GetId();
                $busId = $sub->lineOfBusinessID;
                if ((int)$key <= 0 || $title === "" || (int)$busId <= 0) { continue; }
                $pId = $this->Category->WpId($busId, 'toristy-category');
                if ($pId <= 0) { continue; }
                $sub->wpParent = $pId;
                $slug = $sub->systemtype . "-$key";
                $this->Category->Update($key, $type, $title, $slug, $sub);
            }
            return true;
        } catch (Exception $e) {}
        return false;
    }

    public function Locations(): bool
    {
        $locations = $this->Datas;
        if (count($locations) <= 0) { return true; }
        try {
            $type = "toristy-location";
            $countries = [];
            foreach ($locations as $location) {
                if (!$location instanceof Location) { continue; }
                $title = $location->GetName();
                $key = $location->GetId();
                $cId = $location->GetCountryId();
                if ((int)$key <= 0 || $title === "") { continue; }
                if ($cId > 0) {
                    $countries[$cId]['cities'][] = [$location, $title, $key];
                } else {
                    $countries[$key]['country'] = [$location, $title, $key];
                }
            }
            foreach ($countries as $temps) {
                if (isset($temps['country'])) {
                    list($country, $title, $key) = $temps['country'];
                    $id = $this->Category->Update($key, $type, $title, '', $country);
                    if ($id > 0 && isset($temps['cities'])) {
                        $cities = $temps['cities'];
                        foreach ($cities as $temp) {
                            list($city, $title1, $key1) = $temp;
                            $this->Category->Update($key1, $type, $title1, '', $city, $id);
                        }
                    }
                }
            }
            return true;
        } catch (Exception $e) {}
        return false;
    }

    public function Providers(): bool
    {
        $providers = $this->Datas;
        if (empty($providers)) { return true; }
        try {
            $type = "toristy-provider";
            foreach ($providers as $provider) {
                if (!$provider instanceof Provider) { continue; }
                $key = $provider->GetId();
                $title = $provider->GetName();
                if (strlen($key) <= 0 || $title === '') { continue; }
                $this->Page->Update($key, $type, $title, [], $provider);
            }
            return true;
        } catch (Exception $e) {}
        return false;
    }

    public function Services(): bool
    {
        $services = $this->Datas;
        if (count($services) <= 0) { return true; }
        try {
            $type = "toristy-service";
            foreach ($services as $service) {
                if (!$service instanceof Service) { continue; }
                $key = $service->GetId();
                $title = $service->GetName();
                if ((int)$key <= 0 || $title === '') { continue; }
                $locations = (array)$service->GetLocation();
                $country = (isset($locations['country'])) ? strtolower($locations['country']) : '';
                $city = (isset($locations['city'])) ? strtolower($locations['city']) : '';
                $city = ($country === $city) ? '' : $city;
                $taxes = $this->Taxes([
                    'toristy-category' => $service->lineOfBusinessId,
                    'toristy-type' => $service->servicetypeid,
                    'location-country' => $country,
                    'location-city' => $city
                ]);
                $p = '';
                if ($service->serviceprovider instanceof Provider) {
                    $p = $service->serviceprovider->GetId();
                }
                $service->wpParent = (strlen($p) > 0) ? $this->Page->WpId($p, 'toristy-provider') : 0;
                $this->Page->Update($key, $type, $title, $taxes, $service, $service->wpParent);
            }
            return true;
        } catch (Exception $e) {}
        return false;
    }

    private function Taxes(array $temps): array
    {
        $taxes = [];
        foreach ($temps as $t => $v) {
            $type = (strpos($t, 'location') !== false) ? 'toristy-location' : $t;
            $val = $v;
            $id = 0;
            if (!isset($val)) { continue; }
            if ($type === 'toristy-location') {
                if (strlen($val) <= 0) { continue; }
                $term = $this->Category->BySlug($val, $type);
                if ($term instanceof WP_Term) {
                    $id = $term->term_id;
                }
            } else {
                if ((int)$val <= 0) { continue; }
                $id = $this->Category->WpId($val, $type);
            }
            if ($id > 0) {
                if (array_key_exists($type, $taxes)) {
                    $taxes[$type][] = $id;
                } else {
                    $taxes[$type] = [$id];
                }
            }
        }
        return $taxes;
    }
}