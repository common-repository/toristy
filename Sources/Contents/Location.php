<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Contents;


class Location
{
    use JsonClass;

    private $id = 0;

    private $locationname = '';

    private $countryid = 0;

    public $countryname = '';

    public $locationdefault = 0;

    public $geopoint;

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

    public function GetName(): string
    {
        return (is_string($this->locationname)) ? $this->locationname : '';
    }

    public function GetCountryId(): int
    {
        return (is_numeric($this->countryid)) ? (int)$this->countryid : 0;
    }
}