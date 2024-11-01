<?php
/**
 * Package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Contents;


use Toristy\Cores\Plugin;

class ServiceType
{
    use JsonClass;

    private $id;

    private $name;

    public $systemtype;

    public $lineOfBusinessID;

    public $wpParent;

    private $name_translations;

    public  $skin = 'toristy-filter';

    public $date;

    public function __construct($content)
    {
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
}