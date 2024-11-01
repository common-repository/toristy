<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Contents;


use Toristy\Cores\Plugin;

class Business
{
    use JsonClass;

    private $id;

    private $name = "";

    private $description = "";

    private $name_translations;

    private $description_translations;

    private $total_serviceTypes;

    private $image;

    public  $skin = 'toristy-filter';

    public $date;

    public function __construct($content)
    {
        $this->Deserialize($content);
    }

    public function GetId(): string
    {
        return $this->id;
    }public function GetName(int $size = 0): string
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
}