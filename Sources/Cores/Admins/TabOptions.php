<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores\Admins;


use Toristy\Cores\Settings;

class TabOptions extends AdminTab
{
    public function __construct(Settings $settings, string $key)
    {
        $this->Title = 'Options';
        parent::__construct($settings, 'toristy_setting_options', [], 'options', $key);
    }

    public function Group($inputs)
    {
        foreach ($inputs as $input => $value) {
            if (is_array($value)) {
                $inputs[$input] = $this->Group($value);
            } elseif (isset($inputs["pc"]) && $inputs["pc"][0] === "#") {
                $inputs["pc"] = ltrim($inputs["pc"], "#");
            } else if (isset($inputs["font"])) {
                if ($inputs["font"] === "") { $inputs["font"] = "lato"; }
            }
        }
        return $inputs;
    }

    public function Load($args)
    {
        $key = $args['key'];
        $name = $args["name"];
        $default = $args["default"];
        $note = (isset($args['note'])) ? $args['note'] : '';
        $options = (isset($this->Options[$key])) ? $this->Options[$key] : [];
        $value = (isset($options[$name]) && $options[$name] !== '') ? $options[$name] : '';
        if ($name === "pc")
        {
            echo '<input type="text" name="'.$this->Option.'['.$key.']['.$name.']" value="#'.$value.'" class="toristy-color" data-default-color="#53777a" />';
        }
        else if ($name === "fontsize")
        {
            $value = (int)$value;
            if ($value === 0) { $value = (int)$default; }
            $count = 100;
            $select = '<select name="'.$this->Option.'['.$key.']['.$name.']">';
            for ($i = 1; $i < $count; $i++)
            {
                if ($i === $value)
                { $select .= "<option value='$i' selected='selected'>$i</option>"; }
                else { $select .= "<option value='$i'>$i</option>"; }
            }
            echo "$select</select>";
        }
        else if ($name === "font")
        {
            $names = array_unique([
                "Lato", "Raleway", "Crete Round", "Kanit", "Crimson Text",
                "Cardo", "Oswald", "Quicksand", "Judson", "Archivo", "Arial",
                "Arial Black", "Comic Sans MS", "Courier New", "Impact", "Lucida Console",
                "Lucida Sans Unicode", "Montserrat", "Open Sans", "Tahoma", "Times New Roman",
                "Trebuchet MS", "Verdana"
            ]);
            sort($names);
            $select = '<select name="'.$this->Option.'['.$key.']['.$name.']">';
            foreach ($names as $n)
            {
                $ln = strtolower($n);
                $ln = str_replace(" ", "", $ln);
                if ($ln === $value)
                { $select .= "<option value='$ln' selected='selected'>$n</option>"; }
                else { $select .= "<option value='$ln'>$n</option>"; }
            }
            echo "$select</select>";
        }
        else if ($name === "zoom") {
            $value = (int)$value;
            if ($value === 0) { $value = (int)$default; }
            $count = 21;
            $select = '<select name="'.$this->Option.'['.$key.']['.$name.']">';
            for ($i = 10; $i < $count; $i++)
            {
                if ($i === $value)
                { $select .= "<option value='$i' selected='selected'>$i</option>"; }
                else { $select .= "<option value='$i'>$i</option>"; }
            }
            echo "$select</select>";
            echo "<p>Map zoom length, option from 10 - 20</p>";
        } elseif ($key === 'filters') {
            echo '<input class="toristy-input" name="'.$this->Option.'['.$key.']['.$name.']" type="text" value="'.$value.'" />';
            echo "<p style='color: red'>$note</p>";
        } else {
            $site = get_site_url();
            $finds = ['#^http(s)?://#', '/^www\./', ''];
            $site = preg_replace( $finds[0], $finds[2], $site );
            $site = preg_replace( $finds[1], $finds[2], $site );
            echo '<input type="text" class="regular-text" name="'.$this->Option.'['.$key.']['.$name.']" value="'.$value.'" placeholder="Google map api key!">';
            echo"<p>If the API key is set, Google map will show up on all service, when location is set.</p>";
            echo "<div>
                    <p>Sign-in or register for an API key at: <a target='_blank' href='https://console.cloud.google.com'>Google Cloud</a>, then go to APIs and Services and add site domain: <span style='background-color: blue; color: white'>".$site."/*</span> under Application restrictions, HTTP referrers (websites).</p>
                    <p>Choose Custom Design in Service Settings Tab to see the map.</p>
                </div>";
        }
    }

    protected function Populate()
    {
        /*$this->Add('prices', 'Currency Allowed', [
            "default" => "",
            "name" => "prices",
            "key" => 'filters',
            "note" => 'Show only services with the defined currency on the front-end. when this is changed, sync again in Api options, examples(€, £, $).'
        ], 'Service Filters');*/

        $this->Add('fontsize', 'Widget Font Size', [
            "default" => "15",
            "name" => "fontsize",
            "key" => 'widget'
        ], 'Toristy Widget');
        $this->Add('font-family', 'Widget Font Family', [
            "default" => "lato",
            "name" => "font",
            "key" => 'widget'
        ], 'Toristy Widget');
        $this->Add('pc', 'Widget color', [
            "default" => "53777a",
            "name" => "pc",
            "key" => 'widget'
        ], 'Toristy Widget');

        $this->Add('key', 'Google maps API key', [
            "default" => "",
            "name" => "api",
            "key" => 'google'
        ], 'Google Maps');
        $this->Add('zoom', 'Zoom size', [
            "default" => "1",
            "name" => "zoom",
            "key" => 'google'
        ], 'Google Maps');
    }
}