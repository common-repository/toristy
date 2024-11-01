<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Helpers\Parses;


use Toristy\Cores\Plugin;
use Toristy\Helpers\Domain;
use Toristy\Helpers\Populate;
use WP_Post;

class FilterParse extends Populate
{
    public function __construct(array $pairs, ?WP_Post $post = null)
    {
        $this->Path = Plugin::GetPageSlug();
        $this->Pages = [[Plugin::GetName(), Domain::PageUrl($this->Path), true]];
        if ($post instanceof WP_Post) {
            $this->Pages[] = [$post->post_title, get_the_permalink($post->ID), true];
            $this->Id = $post->ID;
        }
        parent::__construct();
        $this->Match($pairs);
    }

    public function GetTitle(): string
    {
        return implode(', ', $this->Title);
    }

    public function GetCategories(): array
    {
        return parent::Categories();
    }

    public function GetTypes(): array
    {
        return parent::Types();
    }

    public function GetFilter(): string
    {
        $title = Plugin::CutWordSize($this->Get('info-filter', ''), 2);
        $types = [];//$this->Types();
        $types = (!empty($types)) ? "<div class='toristy-random-items'><h4>Types</h4>".implode('', $types)."</div>" : '';
        $locations = $this->Locations();
        $locations = (!empty($locations)) ? "<div class='toristy-random-items'><h4>Locations</h4>".implode('', $locations)."</div>" : '';
        $providers = [];//$this->Providers(-1, $this->Id);
        $providers = (!empty($providers)) ? "<div class='toristy-random-items'><h4>Providers</h4>".implode('', $providers)."</div>" : '';
        return "<div class='toristy-search-filter'>
                <h4>$title</h4>
                ".$this->Form(true)."</div>$types$providers$locations";
    }

    public function GetServices(): array
    {
        list($temps, $total) = $this->Services(10, $this->Paged, $this->Id);
        $posts = [];
        foreach ($temps as $temp) {
            $posts[] = $temp;
        }
        $services = $this->Generate($posts);
        if (!empty($services)) {
            $this->Total = $total;
            return [implode('', $services), ''];
        }
        return ['<div><span>No Services Found!</span></div>', ' center'];
    }

    public function GetPagination(): string
    {
        return $this->Pagination();
    }
}