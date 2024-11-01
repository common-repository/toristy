<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores\Codes;


use Toristy\Cores\ShortCode;

class SearchCode extends ShortCode
{
    public function __construct()
    {
        parent::__construct('search');
    }

    public function Render($params = [], $content = null): string
    {
        $action = esc_url( home_url( "/" ) );
        $title = (isset($params['title'])) ? $params['title'] : '';
        $title = (strlen($title) > 0) ? "<h1>$title</h1>" : '';
        $btn = (isset($params['button'])) ? $params['button'] : '';
        $image = (isset($params['image']) && strlen($params['image']) > 0) ? : 'https://cdn.toristy.com/2019/2/12/5GYTbOQw7Sjop6vnZzMd.png';
        $btn = (strlen($btn) > 0) ? "<div class='toristy-button-holder'><button class='toristy-btn toristy-btn-search'>$btn</button></div>" : '';
        return "<div class='toristy-search'>
                <div class='toristy-search-bar'>
                    $title
                    <form role='search' method='get' action='$action'>
                        <div><input class='toristy-input toristy-search-item' name='toristy-search' type='text'></div>
                        $btn
                    </form>
                </div>
            </div>";
    }
}