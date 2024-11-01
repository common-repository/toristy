<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores\Codes;


use Toristy\Contents\Service;
use Toristy\Cores\Option;
use Toristy\Cores\Page;
use Toristy\Cores\Plugin;
use Toristy\Cores\ShortCode;
use Toristy\Helpers\Domain;
use WP_Post;
use WP_Query;

class FamousCode extends ShortCode
{
    protected $AllowTypes = ['services'];
    /**
     * @var Page
     */
    private $Page;

    private $Types = ['feature', 'recommend'];

    public function __construct()
    {
        $this->Page = Plugin::Get('page');
        parent::__construct('famous');
    }

    public function Render($params = [], $content = null): string
    {
        $type = (isset($params['type']) && in_array(strtolower($params['type']), $this->Types)) ? strtolower($params['type']) : '';
        $ids = (strlen($type)) ? (array)Option::Get('toristy_'.$type.'_services', [], true) : [];
        if (!empty($ids)) {
            return $this->Load(array_unique(array_values($ids)));
        }
        return "";
    }

    private function Load(array $ids): string
    {
        if (count($ids) <= 2) {
            return "";
        }
        $args = [
            'post_type'     => ['toristy-service'],
            'post_status'   => 'publish',
            'post__in' => $ids
        ];
        $loop     = new WP_Query($args);
        $posts    = $loop->posts;
        //reset the query post data.
        wp_reset_postdata();
        if (count($posts) > 0) {
            return $this->Generate($posts);
        }
        return "";
    }

    private function Generate(array $posts): string
    {
        $services = [];
        foreach ($posts as $post) {
            if ($post instanceof WP_Post) {
                $this->Page->Meta($post->ID, 'toristy-service');
                if (property_exists($post, 'toristy') && $post->toristy instanceof Service) {
                    $toristy = $post->toristy;
                    $title = Plugin::CutSize($toristy->GetName(), 33);
                    $link = get_the_permalink($post->ID);
                    $price = $toristy->GetText("price");
                    $des = Plugin::CutSize(strip_tags($toristy->GetText('description')), 100);
                    $image = $toristy->GetFeatureImage();
                    $location = $toristy->GetStreet();
                    $services[] = "<article class='toristy-item'><a href='$link'>
                    <div class='toristy-item-image'><div style='background-image: url($image)'></div></div>
                    <div class='toristy-item-info'>
                        <div>
                            <h2>$title</h2>
                            <p>$des</p>
                        </div>
                        <div>
                            <p>$location</p>
                        </div>
                    </div>
                    <div class='toristy-item-price'><span>from</span><span>$price</span></div>
                    </a></article>";
                }

            }
        }
        if (!empty($services)) {
            $css = Domain::GetContent(Domain::Url('assets/css/featured-min.css'));
            $style = "
               <style>$css</style>
            ";
            array_unshift($services, "$style<div class='toristy-items'>");
            $services[] = "</div>";
        }
        return implode('', $services);
    }
}