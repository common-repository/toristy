<?php
/**
 * Package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Helpers;



use Toristy\Contents\Business;
use Toristy\Contents\Provider;
use Toristy\Contents\Service;
use Toristy\Contents\ServiceType;
use Toristy\Cores\Category;
use Toristy\Cores\Option;
use Toristy\Cores\Page;
use Toristy\Cores\Plugin;
use WP_Post;
use WP_Query;
use WP_Term;

abstract class Populate
{
    /**
     * @var int
     */
    protected $Paged;
    /**
     * @var string
     */
    protected $Path;
    /**
     * @var array
     */
    protected $Paths = [];
    /**
     * @var array
     */
    private $Skips = [];
    /**
     * @var Category
     */
    private $Category;
    /**
     * @var Page
     */
    protected $Page;
    /**
     * @var array
     */
    private $Terms = [];
    /**
     * @var Provider
     */
    protected $Provider;
    /**
     * @var int
     */
    protected $Id = 0;
    /**
     * @var int
     */
    protected $Total = 0;
    /**
     * @var string
     */
    protected $Search = '';
    /**
     * @var array
     */
    protected $Pages = [];
    /**
     * @var array | string[]
     */
    private $Matches = [
        'toristy-location',
        'toristy-city',
        'toristy-category',
        'toristy-type'
    ];
    /**
     * @var array
     */
    protected $Title = [];

    /**
     * @var array
     */
    private $Settings = [];

    protected function __construct()
    {
        $this->Paged = (int)get_query_var('paged');
        $this->Category = Plugin::Get('category');
        $this->Page = Plugin::Get('page');
        $this->Settings = array_merge($this->Settings, (array)Option::Get('toristy_pages', [], true));
    }

    protected function Get(string $key, string $default) : string
    {
        return (isset($this->Settings[$key])) ? $this->Settings[$key] : $default;
    }

    public function Breadcrumb(): string
    {
        $paths = [];
        foreach ($this->Pages as $page) {
            list($title, $slug, $bol) = $page;
            $paths[] = ($bol && end($this->Pages) !== $page) ? "<a class='toristy-breadcrumb-item' href='$slug'>$title</a>" : "<span class='toristy-breadcrumb-item disabled'>$title</span>";
        }
        return implode('<span class="toristy-space"> / </span>', $paths);
    }

    private function Link(string $link, string $path = 'filter'): string
    {
        if (empty($this->Paths) || strlen($path) <= 0) { return $link; }
        $search = (substr($path, -1) === '/') ? substr($path, 0, -1) : $path;
        $replace = ($this->Id > 0) ? implode('/', $this->Paths) : "$search/".implode('/', $this->Paths);
        return str_replace($search, $replace, $link);
    }

    protected function Match(array $pairs): void
    {
        if (isset($pairs['toristy-filter']) && $pairs['toristy-filter'] !== '') {
            $this->Search = "--filter--";
            $search = 'All';
            $this->Pages[] = [$search, "", false];
        } elseif (isset($pairs['toristy-search']) && $pairs['toristy-search'] !== '') {
            $this->Search = str_replace('-', ' ', $pairs['toristy-search']);
            $search = ucwords($this->Search);
            $this->Pages[] = [$search, "", false];
            $this->Title[] =  "Search for: $search";
        } else {
            $skip = false;
            $matches = ['toristy-location', 'toristy-city'];
            foreach ($pairs as $key => $pair) {
                $pair = strtolower($pair);
                if (!in_array($key, $this->Matches) || strlen($pair) <= 0) { continue; }
                if (!$skip && !is_null($term = $this->Category->BySlug($pair, $key))) {
                    $this->Terms[$key] = $term;
                    $title = $term->name;
                    if (in_array($key, $matches)) {
                        $this->Paths[] = $term->slug;
                        $this->Pages[] = [$title, get_term_link($term, $key), true];
                    } else {
                        $link = $this->Link(get_term_link($term, $key));
                        $this->Pages[] = [$title, $link, true];
                    }
                    $this->Title[] = $title;
                } else {
                    if ($key === $matches[0]) {
                        $skip = true;
                    }
                    $title = ucfirst($pair);
                    $this->Pages[] = [$title, "", false];
                    $this->Title[] = $title;
                }
            }
        }
    }

    private function Args(): array
    {
        $args = [];
        foreach ($this->Terms as $term)
        {
            if (!$term instanceof WP_Term || (isset($this->Terms['toristy-type']) && $term->taxonomy === 'toristy-category')) { continue; }
            $args[] = [
                "taxonomy" => $term->taxonomy,
                "field"    => "slug",
                "terms"    => $term->slug,
                "operator" => "IN"
            ];
        }
        if (count($args) > 1) { $args["relation"] = "AND"; }
        return $args;
    }

    /**
     * @param array $posts
     * @param array $excludes examples: [description, location]
     * @param int $len
     * @return array
     */
    protected function Generate(array $posts, array $excludes = [], int $len = 10) : array
    {
        $services = [];
        foreach ($posts as $post) {
            if ($post instanceof WP_Post && !is_null($toristy = $this->Page->Meta($post->ID, $post->post_type)) && $toristy instanceof Service) {
                $title = $toristy->GetName($len);
                $link = get_the_permalink($post->ID);
                $price = $toristy->GetPrice();
                $price = (strlen($price) > 0) ? "<span>from</span><span>$price</span>" : "<span></span><span>No available price</span>";
                $des = (in_array('description', $excludes)) ? '' : '<p>'.$toristy->GetDescription(true, 25).'</p>';
                $image = $toristy->GetFeatureImage();
                $location = (in_array('location', $excludes)) ? '' : '<p>'.$toristy->GetStreet().'</p>';
                $services[] = "<article class='toristy-item'><a href='$link'>
                    <div class='toristy-item-image'><div style='background-image: url($image)'></div></div>
                    <div class='toristy-item-info'>
                        <div>
                            <h2>$title</h2>
                            $des
                        </div>
                        <div class='toristy-more'>
                            <div>$location</div>
                            <div class='toristy-item-price'>$price</div>
                        </div>
                    </div>
                    
                </a></article>";
            }
        }
        return $services;
    }

    /**
     * @param int $total total of services
     * @param int $paged page services to get
     * @param int $id parent id
     * @param array $ids post ids
     * @param bool $not not: true, in: false
     * @param bool $rand randomize services: true
     * @return array [posts, count]
     */
    protected function Services(int $total = -1, int $paged = 0, $id = 0, array $ids = [],
                                bool $not = false, bool $rand = false) : array
    {
        $arg = $this->Args();
        $args = [
            'post_type'      => 'toristy-service',
            'post_status'    => 'publish',
            'posts_per_page' => $total
        ];
        if ($paged > 0) { $args['paged'] = $this->Paged; }
        if (!empty($ids)) {
            if ($not) {
                $args['post__not_in'] = $ids;
            } else {
                $args['post__in'] = $ids;
            }
        }
        if (!empty($arg)) { $args["tax_query"] = $arg; }
        if ($rand) { $args['orderby'] = 'rand'; }
        if ($id > 0) { $args['post_parent'] = $id; }
        if ($this->Search !== '' && $this->Search !== '--filter--') { $args['s'] = $this->Search; }
        $loops     = new WP_Query($args);
        $posts    = $loops->posts;
        //reset the query post data.
        wp_reset_postdata();
        return [$posts, $loops->max_num_pages];
    }

    /**
     * @param int $total total of providers
     * @param int $id post id
     * @param bool $not not: true, in: false
     * @return array
     */
    protected function Providers(int $total = -1, int $id = 0, bool $not = true): array
    {
        $args = [
            'post_type'      => 'toristy-provider',
            'post_status'    => 'publish',
            'posts_per_page' => $total
        ];
        if ($id > 0) {
            if ($not) {
                $args['post__not_in'] = [$id];
            } else {
                $args['post__in'] = [$id];
            }
        }
        $loops     = new WP_Query($args);
        $posts    = $loops->posts;
        //reset the query post data.
        wp_reset_postdata();
        $providers = [];
        if (!empty($posts)) {
            foreach ($posts as $post) {
                if ($post instanceof WP_Post && !is_null($toristy = $this->Page->Provider($post)) && $toristy instanceof Provider) {
                    $name = $toristy->GetName();
                    $link = get_the_permalink($post->ID);
                    $providers[] = "<a href='$link'>$name</a>";
                }
            }
        }
        return $providers;
    }

    /**
     * @param string $type taxonomy type
     * @param bool $hide empty hide: true, show: false
     * @param bool $skip cache data include: false, exclude: true
     * @return array
     */
    private function Termsx(string $type, bool $hide, bool $skip) : array
    {
        $terms = get_terms(['taxonomy' => $type, 'hide_empty' => $hide]);
        if (count($terms) > 0 && !$skip) {
            foreach ($terms as $term) {
                $this->Category->Meta($term->term_id, $type);
            }
        }
        return $terms;
    }

    /**
     * @param array $args [total: int, hide_empty: bool, taxonomy: string, orderby: id, order: ASC, exclude: array of ids]
     * @param bool $skip
     * @return array
     */
    protected function Terms(array $args, bool $skip) : array
    {
        return $this->Category->All($args);
    }

    protected function Locations(): array
    {
        $terms = $this->Terms(['taxonomy' => 'toristy-location', 'hide_empty' => true], true);
        $items = [];
        $locations = [];
        if (isset($this->Terms['toristy-city'])) {
            $locations[] = $this->Terms['toristy-city']->term_id;
        }
        if (isset($this->Terms['toristy-location'])) {
            $locations[] = $this->Terms['toristy-location']->term_id;
        }
        foreach ($terms as $term) {
            if ($term instanceof  WP_Term) {
                if (in_array($term->term_id, $locations)) { continue; }
                $link = get_term_link($term, $term->taxonomy);
                $items[] = "<a href='$link'>$term->name <span>($term->count)</span></a>";
            }
        }
        return $items;
    }

    protected function Categories(bool $raw = false) : array
    {
        $type = 'toristy-category';
        $categories = [];
        $terms = $this->Terms(['taxonomy' => $type, 'hide_empty' => !$raw], false);
        if ($raw) {
            return $terms;
        }
        $id = (isset($this->Terms[$type])) ? $this->Terms['toristy-category']->term_id : 0;
        foreach ($terms as $term) {
            if ($term instanceof WP_Term && !is_null($toristy = $this->Category->Meta($term->term_id, $type)) && $term->count > 0 && $toristy instanceof Business) {
                $name = $toristy->GetName();
                $slug = $term->slug;
                $link = $this->Link(get_term_link($term, $term->taxonomy));
                $class = ($term->term_id === $id) ? ' selected' : '';
                $categories[] = "
                <div class='toristy-category-item$class' data-name='$slug'>
                    <a href='$link'>
                        <div><span>$name</span></div>
                        <div><span class='toristy-icon-$slug'/></div>
                    </a>
                </div>
                ";
            }
        }
        return $categories;
    }

    protected function Types(): array
    {
        $id = 0; $key = 0; $items = [];
        if (isset($this->Terms['toristy-category']) && $this->Terms['toristy-category'] instanceof WP_Term) {
            $key = $this->Terms['toristy-category']->term_id;
        }
        if ($key === 0) { return $items; }
        if (isset($this->Terms['toristy-type']) && $this->Terms['toristy-type'] instanceof WP_Term) {
            $id = $this->Terms['toristy-type']->term_id;
        }
        $type = 'toristy-type';
        $terms = $this->Terms(['taxonomy' => $type, 'hide_empty' => true], false);
        foreach ($terms as $term) {
            if ($term instanceof WP_Term && !is_null($toristy = $this->Category->Meta($term->term_id, $type)) && $term->count > 0 && $toristy instanceof ServiceType) {
                if (($key <= 0 || $key === (int)$toristy->wpParent)) {
                    $class = ($term->term_id === $id) ? ' selected' : '';
                    $name = $toristy->GetName();
                    $slug = $term->slug;
                    $link = $this->Link(get_term_link($term, $term->taxonomy));
                    $items[] ="
                <div class='toristy-category-item type-item$class' data-name='$slug'>
                    <a href='$link'>
                        <div><span>$name</span></div>
                        <div><span class='toristy-icon-$slug'/></div>
                    </a>
                </div>
                    ";
                }

            }
        }
        return $items;
    }

    protected function Pagination(): string
    {
        // Get total number of pages
        $total = $this->Total;
        $paged = $this->Paged;
        $links = '';
        // Only paginate if we have more than one page
        if ( $total > 1 )  {
            // Structure of “format” depends on whether we’re using pretty permalinks
            $format = empty( get_option('permalink_structure') ) ? '&page=%#%' : 'page/%#%/';
            $links = paginate_links([
                'base' => get_pagenum_link(1) . '%_%',
                'format' => $format,
                'current' => max(1, $paged),
                'total' => $total,
                'mid_size' => 4,
                'type' => 'nav',
                'prev_text' => '',
                'next_text' => ''
            ]);
            if (strpos($links, 'class="prev') === false) {
                $links = "<span aria-current='page' class='prev page-numbers disabled'></span>$links";
            } else if (strpos($links, 'class="next') === false) {
                $links = "$links<span aria-current='page' class='next page-numbers disabled'></span>";
            }
        }
        return (strlen($links) > 0) ? "<nav class='toristy-pagination'>$links</nav>" : '';
    }

    protected function Form(bool $skip): string
    {
        $button = ($skip) ? "" : "<div class='toristy-button-holder'>
                <button class='toristy-btn toristy-btn-search'>Search</button></div>";
        return "
        <form class='toristy-search-form'>
            <div><input class='toristy-input toristy-search-item' name='toristy-search' type='text'></div>
            $button
        </form>";
    }
}