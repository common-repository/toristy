<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Contents;


use Toristy\Cores\Option;
use Toristy\Cores\Plugin;

class Service
{
    use JsonClass;

    private $id;

    public $servicetypeid;

    public $systemtype;

    private $name;

    private $name_translations;

    private $description;

    private $cancellation_text;

    private $cancellation_policy;

    private $description_translations;

    private $image;

    private $images;

    private $location;

    private $starting_price;

    public $apiurl;

    public $serviceprovider;

    public $lineOfBusinessId;

    private  $includes;

    public  $excludes;

    public  $requirements;

    public  $attentions;

    public $serviceType;

    public $serviceVariants;

    public $date;

    public $skin = 'toristy-service';

    public $post;

    public $widget;

    public $wpParent;

    public function __construct($content)
    {
        $this->Maps = ['serviceprovider' => Provider::class];
        $this->Deserialize($content);
    }

    public function GetId(): string
    {
        return $this->id;
    }

    public function GetName(int $size = 0): string
    {
        $names = (array)$this->name_translations;
        $name = (isset($names[$this->Lang()]) && false !== $names[$this->Lang()]) ? $names[$this->Lang()] : $this->name;
        return ($size > 0) ? Plugin::CutWordSize($name, $size) : $name;
    }

    public function GetDescription(bool $strip, int $size = 0): string
    {
        $descriptions = (array)$this->description_translations;
        $des = (is_array($descriptions) && array_key_exists($this->Lang(), $descriptions) && false !== $descriptions[$this->Lang()]) ? $descriptions[$this->Lang()] : $this->description;
        $des = ($strip) ? strip_tags($des) : $des;
        if ($size === 0 && !$strip) {
            $des = Plugin::RemoveCssFromString($des);
        }
        $des = ($size > 0) ? Plugin::CutWordSize($des, $size) : $des;
        return $des;
    }

    public function GetPrice(): string
    {
        $data = '';
        $prices = (isset($this->starting_price)) ? (array)$this->starting_price : [];
        $sign = (isset($prices["currencySymbol"]) && is_string($prices["currencySymbol"])) ? trim($prices["currencySymbol"]) : "";
        $price = (isset($prices["price"]) && is_string($prices["price"])) ? trim($prices["price"]) : "";
        if ($price !== "") { $data = "$sign ". number_format($price, 2, '.', ',');; } //else { $data = 'No available price'; }
        return $data;
    }

    /**
     * Get image depending of the size
     *
     * @param  string  $size  small / medium / large / orig
     *
     * @return string
     */
    public function GetImage(string $size): string
    {
        $images = (array)$this->image;
        return (isset($images[$size]) && $images[$size]) ? $images[$size] : "https://cdn.toristy.com/2019/2/12/5GYTbOQw7Sjop6vnZzMd.png";
    }

    private function FeatureImage(array $images): string
    {
        if (!is_array($images)) {
            return '';
        }
        if (isset($images['orig'])) {
            return $images['orig'];
        } else if (isset($images['large'])) {
            return $images['large'];
        } else if (isset($images['medium'])) {
            return $images['medium'];
        }
        return '';
    }

    /**
     * @param bool $bol return default if not image found
     * @return string
     */
    public function GetFeatureImage(bool $bol = false): string
    {
        $image = $this->FeatureImage((array)$this->image);
        if (strlen($image) <= 0 && isset($this->images)) {
            $images = (array)$this->images;
            foreach ($images as $imgs) {
                $image = $this->FeatureImage((array)$imgs);
                if (strlen($image) > 0) {
                    return $image;
                }
            }
        }
        return (strlen($image) > 0) ? $image : (($bol) ? "https://cdn.toristy.com/2019/2/12/5GYTbOQw7Sjop6vnZzMd.png": '');
    }

    public function GetGallery(): array
    {
        $count = 0;
        $images = [];
        if (isset($this->images) && count($this->images) > 1) {
            foreach ($this->images as $image) {
                if ($count === 5) {
                    break;
                }
                $image = (object)$image;
                if (isset($image->large) || isset($image->orig)) {
                    $src = isset($image->orig) ? $image->orig : $image->large;
                    if ($count === 0) {
                        $images[] = "<img class='toristy-service-image-preview' src='$src'/>";
                    }
                    $images[] = "<div class='toristy-service-image-thumb' style='background-image: url(" . $src . ");'></div>";
                    $count = $count + 1;
                }
            }
        }
        return $images;
    }

    public function GetImages(): array
    {
        $count = 0;
        $images = [];
        if (isset($this->images) && count($this->images) > 1)
        {
            foreach ($this->images as $image)
            {
                if ($count === 5) { break; }
                if (isset($image->large) || isset($image->orig))
                {
                    $src = isset($image->orig) ? $image->orig : $image->large;
                    $images[] = $src;
                    $count = $count + 1;
                }
            }
        }
        return $images;
    }

    /**
     * @param  string  $name city, country, [city, country]
     * null or "" return everything.
     *
     * @return string
     */
    public function GetStreet($name = ""): string
    {
        $names = (isset($name) && $name !== "") ? (array)$name : ["city", "country"];
        $location = isset($this->location) ? (array)$this->location : [];
        if (empty($location)) { return ""; }
        $data = "";
        foreach ($names as $name)
        {
            if (isset($location[$name]))
            {
                $data = ($data !== "") ? $data.", ".$location[$name] : $location[$name];
            }
        }
        return $data;
    }

    public function GetLocation(array $name = []): array
    {
        $names = (!empty($name)) ? (array)$name : ["city", "country"];
        $location = isset($this->location) ? (array)$this->location : [];
        if (empty($location)) { return []; }
        $data = [];
        foreach ($names as $name)
        {
            if (isset($location[$name]))
            {
                $data[$name] = $location[$name];
            }
        }
        return $data;
    }

    public function GetStreetWithMap(): array
    {
        $result = [];
        $data = $this->GetStreet(['line1', 'line2', 'city', 'state', 'postcode', 'country']);
        if ($data !== "") { $result['street'] = $data; }
        $map = (array)Option::Get('toristy_setting_options/google', [], true);
        if (isset($map['api']) && $map['api'] !== "")
        {
            $lat = $this->GetStreet('lat');
            $lng = $this->GetStreet('lng');
            if ($lat !== "" && $lng !== "")
            {
                $map['zoom'] = (int)$map['zoom'];
                $lat = floatval($lat);
                $lng = floatval($lng);
                $result['maps'] = array_merge(["map" => ["lat" => $lat, "lng" => $lng]], $map);
            }
        } else {
            $result['maps'] = [];
        }
        return $result;
    }

    /**
     * @param string $name cancellation | attention | includes | excludes
     * @return string
     */
    public function GetText(string $name) : string
    {
        $data = '';
        if (strlen($name) <= 0) {
            return $data;
        }
        $key = strtolower($name);
        $datas = [
            'cancellation' => $this->cancellation_text,
            'attentions' => $this->attentions,
            'includes' => $this->includes,
            'excludes' => $this->excludes
        ];
        if (isset($datas[$key])) {
            $data = $datas[$key];
        }
        return $data;
    }

    /**
     * @param array $names [cancellation, attention, includes, excludes]
     * @return array
     */
    public function GetTexts(array $names) : array
    {
        $datas = [];
        foreach ($names as $name) {
            $key = strtolower($name);
            $data = $this->GetText($key);
            if (strlen($data) > 0) {
                $datas[$key] = $data;
            }
        }
        return $datas;
    }
}