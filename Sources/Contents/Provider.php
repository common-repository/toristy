<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Contents;


use Toristy\Cores\Option;
use Toristy\Cores\Plugin;

class Provider
{
    use JsonClass;

    private $id;

    private $uuid;

    private $name;

    private $name_translations;

    private $description;

    private $description_translations;

    private $location;

    private $logoimage;

    public $iconimage;

    public $website;

    public $total_services;

    public $apiurl;

    public $images;

    private $reviews;

    private $total_reviews;

    private $rating;

    public $date;

    public $skin = 'toristy-provider';

    public function __construct($content)
    {
        $this->Maps = ['reviews' => ProviderRating::class];
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
        return ($size > 0) ? Plugin::CutSize($name, $size) : $name;
    }

    public function GetDescription(bool $strip, int $size = 0): string
    {
        $descriptions = (array)$this->description_translations;
        $des = (is_array($descriptions) && array_key_exists($this->Lang(), $descriptions) && false !== $descriptions[$this->Lang()]) ? $descriptions[$this->Lang()] : $this->description;
        $des = ($strip) ? strip_tags($des) : $des;
        if ($size === 0 && !$strip) {
            $des = Plugin::RemoveCssFromString($des);
        }
        $des = ($size > 0) ? Plugin::CutSize($des, $size) : $des;
        return $des;
    }

    public function GetReviews(): array
    {
        return (is_array($this->reviews)) ? $this->reviews : [];
    }

    public function GetReview(): string
    {
        $id = $this->GetId();
        $total = (int)$this->total_reviews;
        $key = (string)Option::Get("toristy_api_key", "", true);
        if ($total > 0 && $id > 0 && strlen($key) > 0) {
            $data = implode('&', [
                'es=%23toristyiframe-responsive'.$id,
                'businessid='.$id,
                'et=reviews',
                'apikey='.$key,
                'fontsize=15'
            ]);
            return "<div id='toristyiframe-responsive$id'></div>
            <script async src='https://embed.toristy.com/embed.js?$data' charset='utf-8'></script>";
        }
        return '';
    }
}