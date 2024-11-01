<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Contents;


use Toristy\Cores\Plugin;

class ProviderRating
{
    use JsonClass;

    public $reviewid = '';

    public $reviewsystem = '';

    public $rating = '0.00';

    public $review = '';

    public $date = '';

    public function __construct($content)
    {
        $this->Deserialize($content);
    }

    public function GetPercentage() : float
    {
        return round($this->GetRating() * 100/5);
    }

    /**
     * @return float
     */
    public function GetRating(): float
    {
        $rate = (strlen(trim($this->rating)) > 0) ? $this->rating : '0.00';
        return (is_numeric($rate)) ? number_format($rate, 2) : 0.00;
    }

    /**
     * @return string
     */
    public function GetDate(): string
    {
        return Plugin::AgoTime($this->date);
    }
}