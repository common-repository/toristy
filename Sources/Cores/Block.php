<?php
/**
 * Package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;


use Toristy\Contents\Service;
use Toristy\Helpers\Hook;
use WP_Term;

class Block
{
    /**
     * @var Page
     */
    private $Page;

    /**
     * @var Category
     */
    private $Category;

    /**
     * @var Skin
     */
    private $Skin;

    public function __construct()
    {
        $this->Page = Plugin::Get('page');
        $this->Category = Plugin::Get('category');
        $this->Skin = Plugin::Get('skin');
        Hook::Add("block-1", "init", [$this, "Populate"]);
    }

    public function Populate()
    {
        register_block_type('toristy/action', [
            'editor_script' => 'toristy-blocks',
            'render_callback' => [$this, "Action"]
        ]);

        register_block_type('toristy/spotlight', [
            'editor_script' => 'toristy-blocks',
            'render_callback' => [$this, "Spotlight"]
        ]);

        register_block_type('toristy/category', [
            'editor_script' => 'toristy-blocks',
            'render_callback' => [$this, "Category"]
        ]);

        register_block_type('toristy/type', [
            'editor_script' => 'toristy-blocks',
            'render_callback' => [$this, "Type"]
        ]);

        register_block_type('toristy/location', [
            'editor_script' => 'toristy-blocks',
            'render_callback' => [$this, "Location"]
        ]);
    }

    public function Action($attrs): string
    {
        $id = isset($attrs["id"]) ? (int)$attrs["id"] : 0;
        if ($id <= 0) { return ""; }
        $action = (isset($attrs["action"]) && $attrs["action"] === "calender") ? "toolonly" : "buttononly";
        $service = $this->Page->Meta($id, 'toristy-service');
        if ($service instanceof Service) {
            $title = $service->GetName();
            $display = $this->Page->Widget($service, $action);
            return "<div><h4>$title</h4>$display</div>";
        }
        return '';
    }

    public function Category($attrs): string
    {
        return $this->Tags($attrs, 'toristy-category');
    }

    public function Type($attrs): string
    {
        return $this->Tags($attrs, 'toristy-type');
    }

    private function Extract(array $attrs, array $fixes): array
    {
        $datas = [];
        foreach ($fixes as $fix) {
            $f = strtolower($fix);
            $len = strlen($f);
            $temps = [];
            foreach ($attrs as $name => $attr) {
                $k = strtolower($name);
                if (substr($k, 0, $len) === $f) {
                    $temps[str_replace($f, '', $k)] = $attr;
                }
            }
            if (!empty($temps)) {
                $datas[$f] = $temps;
            }
        }
        //echo '<pre>' . print_r($datas, true);exit();
        return $datas;
    }

    private function Tags(array $attrs, string $type): string
    {
        $color = (isset($attrs["color"]))? $attrs["color"] : "#fff";
        $paint = (isset($attrs["paint"]))? $attrs["paint"] : "#000";
        $flip = (isset($attrs["extra"]) && (bool)$attrs["extra"])? " toristy-more" : "";
        //unset($attrs["color"], $attrs["paint"], $attrs["extra"]);
        $attrs = $this->Extract($attrs, ['one', 'two', 'three']);
        //var_dump($attrs);exit();
        $data = ["<div class='toristy-category-card$flip'>"];
        $data[] = "<style type='text/css'>
                        .toristy-block-content > * { color: $color!important; }
                        .toristy-block-overlay { background-color: $paint; }
                    </style>";
        foreach ($attrs as $key => $attr)
        {
            $id = (isset($attr["id"]))? (int)$attr["id"] : 0;
            if ($id <= 0) { continue; }
            $term =  get_term($id, $type);
            if (!$term instanceof WP_Term) { continue; }
            $name = $term->name;
            $url = get_term_link($id, $type);//home_url("$taxonomy/$path");
            $url = str_replace("%", "", $url);
            $note = (isset($attr["note"]))? Plugin::CutSize($attr["note"], 250) : "";
            $image = (isset($attr["image"]))? $attr["image"] : "https://cdn.toristy.com/2019/2/12/5GYTbOQw7Sjop6vnZzMd.png";
            $data[] = "<a href='$url' class='toristy-block-$key' style='background-image: url(".$image.");'>
                <div class='toristy-block-content'>
                    <h3>$name</h3>
                    <p>$note</p>
                </div>
                <div class='toristy-block-overlay'></div>
            </a>";
        }
        $data[] = "</div>";
        if ($data <= 2) { return ""; }
        return implode("", $data);
    }

    public function Location($attrs): string
    {
        if (empty($attrs) || !isset($attrs["country"])) { return ""; }
        $id = (isset($attrs['city']) && strlen($attrs['city']) > 0) ? (int)$attrs['city'] : (int)$attrs['country'];
        if ($id <= 0) {
            return "";
        }
        $term = $this->Category->WpGet($id);
        if (!isset($term)) {
            return '';
        }
        $posts = $this->Page->All([
            'post_type' => 'toristy-service',
            'posts_per_page' => 4,
            'tax_query' => [
                "taxonomy" => $term->taxonomy,
                "field"    => "slug",
                "terms"    => $term->slug,
                "operator" => "IN"
            ]
        ]);
        if (count($posts) % 2 === 0) {
            $total = count($posts);
            $datas = [];
            foreach ($posts as $post) {
                if (!$post instanceof \WP_Post) {
                    continue;
                }
                $service = $this->Page->Meta($post->ID, 'toristy-service');
                if (!$service instanceof Service) {
                    continue;
                }
                $title = $post->post_title;
                $link = get_the_permalink($post->ID);
                $type = $service->serviceType;
                $price = $service->GetText("price");
                $location = $service->GetStreet();
                $image = $service->GetImage("large");
                $alt = strtolower($title);
                $datas[] = "<article class='toristy-service-item'>
            <a href='$link'>
                <div class='toristy-service-item-image'>
                <img alt='$alt' src='$image'/>
                </div>
                <div class='toristy-service-item-content'>
                    <h3 class='service-title'>$title</h3>
                    <p>$type</p>
                    <p class='toristy-on-right'>$location</p>
                    <span class='toristy-on-right'>$price</span>
                </div>
            </a>
        </article>";
            }
            if (count($datas) % 2 === 0) {
                $note = (isset($attrs["note"]))? Plugin::CutSize($attrs["note"], 500) : "";
                $color = (isset($attrs["color"]))? $attrs["color"] : "";
                $paint = (isset($attrs["paint"]))? $attrs["paint"] : "";
                $flip = (isset($attrs["extra"]) && (bool)$attrs["extra"])? " flip" : "";
                $src = (isset($attrs["image"]))? $attrs["image"] : "https://cdn.toristy.com/2019/2/12/5GYTbOQw7Sjop6vnZzMd.png";
                $url = get_term_link($id, 'toristy-location');
                $name = $term->name;
                $data = implode('', $datas);
                return "<style type='text/css'>
                        .toristy-location-details { color: $color; background-color: $paint; }
                        .toristy-button { border-color: $color !important; }
                        .toristy-button:hover { color: $paint !important; background-color: $color !important; }
                    </style>
                    <div class='toristy-location-card$flip toristy-location-card-$total'>
                    <div class='toristy-location-details'>
                        <div class='toristy-location-image' style='background-image: url(".$src.");'></div>
                        <div class='toristy-location-info'>
                            <h2>$name</h2>
                            <p>$note</p>
                            <a class='toristy-button' href='$url'>Explore $name</a>
                        </div>
                    </div>
                    <div class='toristy-location-services'>$data</div>
                </div>";
            }
        }

        return '';
    }

    public function Spotlight($attrs, $content, $block): string
    {
        return $content;
    }
}