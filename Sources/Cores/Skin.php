<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;


use Toristy\Helpers\Domain;
use Toristy\Helpers\Hook;
use WP_Post;
use WP_Query;
use WP_Term;

class Skin
{
    /**
     * @var array
     */
    private $Skins = [];

    /**
     * @var Page
     */
    private $Page;

    /**
     * @var Category
     */
    private $Category;

    private $Paths = [
        'toristy-page' => "templates/main.php",
        'toristy-category' => "templates/filter.php",
        'toristy-type' => "templates/filter.php",
        'toristy-location' => "templates/filter.php",
        'toristy-provider' => "templates/provider.php",
        'toristy-service' => "templates/service.php",
        'toristy-filter' => "templates/filter.php",
        'toristy-search' => "templates/search.php"
    ];

    private $Taxes = [
        "toristy-filter",
        "toristy-location",
        "toristy-search",
        "toristy-category",
        "toristy-type"
    ];

    private $Types = [
        "toristy-service",
        "toristy-provider",
        "toristy-page"
    ];

    public function __construct(Page $page, Category $category)
    {
        $this->Page = $page;
        $this->Category = $category;
        $this->Skins = [
            'toristy-provider' => 'Toristy Provider Template',
            'toristy-service'  => 'Toristy Service Template',
            'toristy-page' => 'Toristy Page'
        ];
        Hook::Add('skin-1','template_include', [$this, 'Load']);
        Hook::Add('skin-2','term_link', [$this, 'Term'], 10, 3);
        Hook::Add('skin-3','parse_request', [$this, 'Parse'], 10, 3);
        Hook::Add('skin-4','wp_ajax_toristy_search', [$this, 'Search']);
        Hook::Add('skin-5','wp_ajax_nopriv_toristy_search', [$this, 'Search']);
        //Hook::Add('skin-6','admin_footer-edit.php', [$this, 'Remove']);
    }

    public function Remove(): void
    {
        echo "<script type='text/javascript'>
            jQuery('table.wp-list-table a.row-title').contents().unwrap();
        </script>";
    }

    private function Home(object &$queries, string $slug): bool
    {
        if (isset($queries->query_vars) && isset($queries->query_vars["pagename"])) {
            $query = $queries->query_vars;
            $allows = ['filter', 'search'];
            $path = $query["pagename"];
            $part = '';
            $skip = true;
            $terms = [];
            $matches = [];
            $rules = [];
            if ($path === "$slug/filter") {
                $terms['toristy-filter'] = 'filter';
                $matches[] = "toristy-search=filter";
                $rules[] = '(.+?)';
                $skip = false;
            } else {
                foreach ($allows as $allow) {
                    if (strpos($path, "$slug/$allow") !== false) {
                        $skip = false;
                        $path = str_replace("$slug/$allow/", "", $path);
                        $part = $allow;
                        break;
                    }
                }
            }
            if (!$skip) {
                if (empty($terms)) {
                    $paths = explode("/", $path);
                    if ($part === 'filter') {
                        $types = $this->Taxes;
                        foreach ($paths as $path) {
                            foreach ($types as $type) {
                                $term = $this->Category->BySlug($path, $type);
                                if ($term instanceof WP_Term) {
                                    if (!array_key_exists($type, $terms)) {
                                        $terms[$type] = $path;
                                        $matches[] = "$type=$path";
                                        $rules[] = '(.+?)';
                                    } else if ($type === 'toristy-location') {
                                        $terms['toristy-city'] = $path;
                                        $matches[] = "toristy-city=$path";
                                        $rules[] = '(.+?)';
                                    }
                                }
                            }
                        }
                    } else {
                        $name = (isset($paths[0])) ? $paths[0] : '';
                        $terms['toristy-search'] = $name;
                        $matches[] = "toristy-search=$name";
                        $rules[] = '(.+?)';
                    }
                }
                //array_diff(array_values($terms), $paths)
                if (!empty($terms)) {
                    $query["pagename"] = $slug;
                    $queries->query_vars    = array_merge($query, $terms);
                    $queries->matched_rule  = $slug.implode("/", $rules)."/?$";
                    $queries->matched_query = implode("&", $matches);
                    //var_dump($queries);exit();
                    return true;
                }
            }
        }
        return false;
    }

    private function Provider(object &$queries, string $slug): bool
    {
        //var_dump($queries);exit();
        $orig = "$slug/provider/";
        if (isset($queries->request) && strpos($queries->request, $orig) !== false) {
            $request = $queries->request;
            $paths = explode('/', str_replace($orig, '', $request));
            $part = null;
            if (!empty($paths) && count($paths) > 1) {
                $part = array_shift($paths);
            }
            if (isset($part) && strlen($part) > 0) {
                $types = $this->Taxes;
                $types = array_splice($types, -2);
                $terms = ['page' => '', 'toristy-provider' => $part, 'post_type' => 'toristy-provider', 'name' => $part];
                $matches = ["toristy-provider=$part"];
                $rules = [];
                foreach ($paths as $path) {
                    foreach ($types as $type) {
                        $term = $this->Category->BySlug($path, $type);
                        if ($term instanceof WP_Term) {
                            if (!array_key_exists($type, $terms)) {
                                $terms[$type] = $path;
                                $matches[] = "$type=$path";
                                $rules[] = '(.+?)';
                            }
                        }
                    }
                }
                if (!empty($rules)) {
                    $query = $queries->query_vars;
                    unset($query["pagename"], $query["attachment"]);
                    $queries->query_vars    = array_merge($query, $terms);
                    $queries->matched_rule  = "$orig([^/]+)(?:/([0-9]+))?/?$";//.implode("/", $rules)."/?$";
                    $queries->matched_query = implode("&", $matches).'&page=';
                    //var_dump($queries);exit();
                    return true;
                }
            }
        }
        return false;
    }

    public function Parse($queries)
    {
        $slug = Plugin::GetPageSlug();
        if (!is_admin()) {
            //var_dump($queries);exit();
        }
        if ($this->Home($queries, $slug)) {
            return $queries;
        } /*elseif ($this->Provider($queries, $slug)) {
            return $queries;
        }*/
        return $queries;
    }

    public function Term($link, $term, $taxonomy)
    {
        if (strpos($link, "filter%") === false) { return $link; }
        $path = '';
        if ($term instanceof WP_Term && $taxonomy === 'toristy-type') {
            $meta = $this->Category->Meta($term->term_id, $taxonomy);
            if (isset($meta) && property_exists($meta, 'wpParent') && $meta->wpParent > 0) {
                $part = get_term_field('slug', $meta->wpParent, 'toristy-category');
                if (strlen($part) > 0) {
                    $link = str_replace("cat%", $part, $link);
                }
            }
        }
        return str_replace("%", $path, $link);
    }

    public function Load(string $skin): string
    {
        global $post;
        if (is_null($post)) { return $skin; }
        $name = $this->Filter($post);
        $name = (strlen($name) > 0) ? $name : $this->Page->GetSkin($post);
        $url = '';
        $path = (isset($this->Paths[$name])) ? $this->Paths[$name] : "";
        if ($path !== '') {
            $url = Domain::Path($path);
        }
        return (file_exists($url)) ? $url : $skin;
    }

    private function Filter(?WP_Post $post): ?string
    {
        $name = '';
        foreach ($this->Taxes as $tax) {
            if (strlen(get_query_var($tax)) > 0 && $post instanceof  WP_Post) {
                if ($this->Page->GetSkin($post) === 'toristy-page') {
                    $name = ($tax === 'toristy-search') ? $tax : 'toristy-filter';
                }
                break;
            }
        }
        return $name;
    }


    public function Search()
    {
        check_ajax_referer('ajax_nonce', 'nonce');
        $search = stripslashes($_POST['search']);

        //$items = [$search];
        $items = [];
        if (is_string($search) && $search !== '') {
            $items = array_merge($this->Tags($search), $this->Services($search));
        }
        wp_send_json_success($items);
    }

    private function Services(string $search) : array
    {
        $posts = new WP_Query( array(
            'post_type'     => ['toristy-service'],
            'post_status'   => 'publish',
            'nopaging'      => true,
            'posts_per_page'=> -1,
            's' => $search,
        ) );
        $items = [];
        foreach ($posts as $post) {
            if ($post instanceof WP_Post) {
                $items[] = [
                    'label' => "$post->post_title",
                    'value' => get_permalink($post)
                ];
            }
        }
        return $items;
    }

    private function Tags(string $search) : array
    {
        $terms = get_terms([
            'taxonomy'      => ['toristy-location', 'toristy-category', 'toristy-type'],
            'orderby'       => 'id',
            'order'         => 'ASC',
            'hide_empty'    => true,
            'fields'        => 'all',
            'name__like'    => $search
        ]);
        $items = [];
        foreach ($terms as $term) {
            if ($term instanceof  \WP_Term) {
                $items[] = [
                    'label' => "$term->name",
                    'value' => get_term_link($term, $term->taxonomy)
                ];
            }
        }
        return $items;
    }

}