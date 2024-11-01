<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;



abstract class ShortCode
{
    protected $AllowTypes = ['services', 'service', 'category'];

    private $Name;

    public function __construct(string $name)
    {
        $key = '';
        if ($name !== '') {
            $name = strtolower($name);
            if ($name[0] === '-') {
                $this->Name = substr($name, 1);
                $key = "$name";
            } else {
                $this->Name = $name;
                $key = "-$name";
            }
        }
        add_shortcode( "toristy-code$key", [$this, 'Render']);
    }

    public abstract function Render($params = [], $content = null): string;
}