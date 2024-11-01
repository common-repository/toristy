<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Helpers\Parses;


use Toristy\Cores\Option;
use Toristy\Cores\Plugin;
use Toristy\Helpers\Domain;
use Toristy\Helpers\Populate;
use WP_Term;

class HomeParse extends Populate
{
    private $Randoms = [];
    /**
     * @var array
     */
    private $HomeIds;

    public function __construct()
    {
        parent::__construct();
        $this->Path = Plugin::GetPageSlug();
        $this->Pages = [];
        $this->HomeIds = (array)Option::Get('toristy_home_services', [], true);
    }

    public function Featured(): array
    {
        return $this->Famous("sec-one");
    }

    protected function Famous(string $name): array
    {
        $ids = (array)(isset($this->HomeIds[$name])) ? $this->HomeIds[$name] : [];
        if (count($ids) >= 3) {
            $title = '';
            $posts = [];
            foreach ($ids as $k => $i) {
                if (strpos($k, 'title-') !== false) {
                    if (strlen($i) > 0) {
                        $title = Plugin::CutWordSize($i, 5);
                    }
                    continue;
                }
                $id = (int)$i;
                if ($id > 0 && !isset($posts[$id]) && !is_null($post = $this->Page->WpGet($id))) {
                    $posts[$id] = $post;
                }
            }
            $services = (count($posts) === 3) ? parent::Generate($posts, ['description'], 3) : [];
            if (count($services) === 3) {
                return ['title' => $title, 'services' => $services];
            }
        }
        $randoms = $this->Page->All([
            'post_type' => 'toristy-service', 'posts_per_page' => 3, 'orderby' => 'rand',
            'post_status' => 'publish', 'post__not_in' => $this->Randoms
        ]);
        if (count($randoms) === 3) {
            $services = parent::Generate($randoms, ['description'], 3);
            if (count($services) === 3) {
                return ['title' => '', 'services' => $services];
            }
        }
        return [];
    }

    public function Recommended(): array
    {
        return $this->Famous("sec-two");
    }

    private function RamTerms(string $type): array
    {
        return get_terms(['taxonomy' => $type, 'hide_empty' => true, 'number' => 4, 'order' => 'DESC', 'orderby' => 'count']);
    }

    public function Randoms(): array
    {
        $temps = [];
        $types = ['toristy-location', 'toristy-category', 'toristy-type'];
        foreach ($types as $type) {
            $terms = $this->RamTerms($type);
            foreach ($terms as $term) {
                if ($term instanceof  WP_Term) {
                    $link = get_term_link($term, $term->taxonomy);
                    $temps[] = "<a href='$link'>$term->name <span>($term->count)</span></a>";
                }
            }
        }
        shuffle($temps);
        $path = Domain::PageUrl($this->Path. '/filter');
        $count = $this->Page->Count('toristy-service');
        return array_merge(["<a href='$path'>All Services <span>($count)</span></a>"], $temps);
    }

    public function Locations(): array
    {
        return parent::Locations();
    }

    public function GetImage(): string
    {
        return $this->Get('home-image', '');
    }

    public function GetForm(): string
    {
        return parent::Form(false);
    }

    public function GetTitle(): string
    {
        return Plugin::CutWordSize($this->Get('info-home', ''), 10);
    }

    public function GetInfo(): array
    {
        $infos = [];
        $temps = [
            ['title' => 'info-title-1', 'note' => 'info-1'],
            ['title' => 'info-title-2', 'note' => 'info-2']
        ];
        foreach ($temps as $temp) {
            $info = [];
            foreach ($temp as $key => $value) {
                $val = $this->Get($value, '');
                if (strlen($val) > 0) {
                    $num = ($key === 'title') ? 10 : 30;
                    $val = Plugin::CutWordSize($val, $num);
                    $info[] = ($key === 'title') ? "<h2>$val</h2>" : "<p>$val</p>";
                }
            }
            $infos[] = implode('', $info);
        }
        return $infos;
    }
}