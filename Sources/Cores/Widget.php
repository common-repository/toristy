<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;


use Toristy\Helpers\Hook;
use WP_Widget;

abstract class Widget extends WP_Widget
{

    public function __construct($id, $name, array $options = [], array $controls = [])
    {
        parent::__construct($id, $name, array_merge($options, [
            'classname' => "toristy-widget-$id",
            'description' => $name,
            'customize_selective_refresh' => true
        ]), array_merge($controls, ['width' => 400, 'height' => 350]));
        Hook::Add('widget-1-'.$this->name, 'widgets_init', [$this, 'WidInit']);
    }

    public function WidInit()
    {
        $this->Load();
        register_widget($this);
    }

    protected abstract function Load(): void;
}