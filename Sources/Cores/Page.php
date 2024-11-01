<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;


use Exception;
use Toristy\Contents\Provider;
use Toristy\Contents\Service;
use Toristy\Helpers\Hook;
use Toristy\Helpers\Process;
use WP_Post;
use WP_Query;

class Page
{
    /**
     * @var string
     */
    private static $Name = 'toristy__';

    /**
     * @var array
     */
    private $Types = [];

    /**
     * @var array
     */
    private $Datas = [];

    /**
     * @var array
     */
    private $Ids = [];

    /**
     * @var array
     */
    private $Metas = [];

    public function __construct()
    {
        Option::Remove(Page::class);
        $this->Types = Plugin::GetCustoms('customs', false);
        Hook::Add('page-1','the_content', [$this, 'Content']);
        Hook::Add('page-2','get_edit_post_link', [$this, 'Link'], 10, 3);
        foreach ($this->Types as $i => $type) {
            $name = explode('-', $type)[1];
            if ($name !== $type) {
                Hook::Add("page-$i-1",'manage_'.$type.'_posts_columns', [$this, 'Column' . ucfirst($name)]);
                Hook::Add("page-$i-2",'manage_'.$type.'_posts_custom_column', [$this, 'Column'], 10, 2);//manage_realestate_posts_custom_column
            }
            ++$i;
        }
    }

    public function ColumnService($columns)
    {
        $columns['status'] = 'Status';
        unset($columns['date']);
        return $columns;
    }

    public function ColumnProvider($columns)
    {
        $columns['status'] = 'Available Services';
        //$columns['empty'] = 'Total Services';
        unset($columns['date']);
        return $columns;
    }

    public function Column($column, $id)
    {
        if ($column === 'status' && !is_null($obj = $this->Meta($id, 'toristy-service')) && $obj instanceof Service) {
            list($bol, $warns, $errors) = $this->ServiceCheck($obj, ['price', 'location']);
            $clas = ($bol) ? (!empty($errors) ? 'errors' : 'warnings') : 'success';
            if (empty($errors)) { $errors[] = 'No errors found'; }
            if (empty($warns)) { $warns[] = 'No warnings found'; }
            $notes = base64_encode(
                json_encode([
                    'title' => '<h3>' . $obj->GetName() . '</h3>',
                   'datas' => implode('', [
                       '<div class="toristy-info"><div><h3>Required</h3><ul><li>' . implode('</li><li>', $errors) . '</li></ul>',
                       "</div><div><h3>Warning</h3>",
                       '<ul><li>' . implode('</li><li>', $warns) . '</li></ul></div></div>'
                   ])
               ]
            ));
            echo "<a href='#' data-notes= '$notes'  class='toristy-tips $clas'></a>";
        } elseif (!is_null($obj = $this->Meta($id, 'toristy-provider')) &&
            $obj instanceof Provider) {
            $count = 0;
            if ($column === 'status') {
                list($bol, $warns, $errors, $count) = $this->ProviderCheck($id, 'toristy-service');
            } elseif ($column === 'empty') {
                $count = (int)$obj->total_services;
            }
            echo "<span>$count</span>";
        }
    }

    public function Link($link, $id, $context): string
    {
        if (function_exists('get_current_screen') && is_admin()) {
            $screen = get_current_screen();
            $key = $this->TyId($id);
            foreach ($this->Types as $type) {
                if (property_exists($screen, 'id') && $screen->id === "edit-$type" && $context === 'display' && strlen($key) > 0) {
                    return get_permalink($id);
                }
            }
        }
        return $link;
    }

    public function Action(?array $actions, ?WP_Post $post): ?array
    {
        if ($post && in_array($post->post_type, $this->Types)) {
            if ($post->post_type === 'toristy-service' &&
                !is_null($obj = $this->Meta($post->ID, 'toristy-service')) && $obj instanceof Service) {
                list($bol, $warns, $errors) = $this->ServiceCheck($obj);
                $notes = json_encode(['title' => '<h3>' . $obj->GetName() . '</h3>',
                                         'datas' => implode('', [
                                             '<div class="toristy-info"><div><h3>Required</h3><ul><li>' . implode('</li><li>', $errors) . '</li></ul>',
                                             "</div><div><h3>Warning</h3>",
                                             '<ul><li>' . implode('</li><li>', $warns) . '</li></ul></div></div>'
                                         ])
                                     ]);
                $clas = ($bol) ? 'errors' : 'success';
                echo "<a href='#' data-notes= '$notes'  class='toristy-tips $clas'></a>";
            }
        }
        return $actions;
    }

    private function Prefix(string $key, string $type): string
    {
        return $type . "__" . $key;
    }

    private function UnPrefix(string $name): string
    {
        $keys = explode('__', $name);
        return end($keys);
    }

    /**
     * @param int $id wordpress id
     * @return string toristy id
     */
    public function TyId(int $id): string
    {
        if ($id > 0) {
            $key = (array_key_exists($id, $this->Ids)) ? $this->UnPrefix($this->Ids[$id]) : '';
            if (strlen($key) <= 0) {
                global $wpdb;
                $str = $wpdb->get_var($wpdb->prepare("SELECT meta_key FROM $wpdb->postmeta WHERE post_id = %s", $id));
                if (isset($str) && strlen($str) > 0) {
                    $key = $this->UnPrefix($str);
                    if (strlen($key) > 0) {
                        $this->Ids[$id] = $str;
                    }
                }
            }
            return $key;
        }
        return '';
    }

    /**
     * @param string $str toristy id
     * @param string $type
     * @return int wordpress id
     */
    public function WpId(string $str, string $type): int
    {
        if (strlen($str) > 0) {
            global $wpdb;
            $key = sanitize_title_with_dashes($str);
            $id = array_search($key, $this->Ids, true);
            if ($id <= 0) {
                $name = $this->Prefix($key, $type);
                try {
                    $id = (int)$wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s", $name));
                } catch (Exception $e) {}
                if ($id > 0) {
                    $this->Ids[$id] = $name;
                }
            }
            return $id;
        }
        return 0;
    }

    /**
     * @param string $str toristy id
     * @param string $type
     * @return WP_Post|null
     */
    public function TyGet(string $str, string $type): ?WP_Post
    {
        $id = $this->WpId($str, $type);
        if ($id > 0) {
            return $this->WpGet($id);
        }
        return null;
    }

    /**
     * @param int $id wordpress id
     * @return WP_Post|null
     */
    public function WpGet(int $id): ?WP_Post
    {
        if ($id > 0) {
            $data = (array_key_exists($id, $this->Datas)) ? $this->Datas[$id] : null;
            if (!$data instanceof WP_Post) {
                $data = get_post($id);
                if ($data instanceof WP_Post) {
                    $this->Datas[$id] = $data;
                } else { $data = null; }
            }
            return $data;
        }
        return null;
    }

    /**
     * @param array $args [post_type: string, paged: int, posts_per_page: int, post__in: array of ids,
     * post__not_in: array of ids, post_parent: int, orderby: string, tax_query: array]
     * @return array
     */
    public function All(array $args = []) : array
    {
        $datas = [];
        if (!array_key_exists('post_type', $args) || !in_array($args['post_type'], $this->Types)) {
            return $datas;
        }
        $type = $args['post_type'];
        $includes = (isset($args['post__in'])) ? $args['post__in'] : [];
        $excludes = (isset($args['post__not_in'])) ? $args['post__not_in'] : [];
        foreach ($this->Datas as $id => $data) {
            $key = array_search($id, $includes, true);
            if (!in_array($id, $excludes) && (empty($includes) || $key > -1) && $data->taxonomy === $type) {
                $datas[] = $data;
                $excludes[] = $id;
                if ($key > -1) {
                    unset($includes[$key]);
                }
            }
        }
        if (!empty($includes)) {
            $args['post__in'] = $includes;
        }
        if (!empty($excludes)) {
            $args['post__not_in'] = $excludes;
        }
        $temps = new WP_Query($args);
        foreach ($temps->posts as $post) {
            $datas[] = $post;
            if (!array_key_exists($post->ID, $this->Datas)) {
                $this->Datas[$post->ID] = $post;
            }
        }
        return $datas;
    }

    public function Update(string $key, string $type, string $title, array $taxes, object $cache, int $parent = 0): int
    {
        if (strlen($key) <= 0 || strlen($title) <= 0 || strlen($type) <= 0) { return 0; }
        $slug = sanitize_title_with_dashes($title);
        $datas = [
            "post_type" => $type,
            "post_name" => $slug,
            "post_title" => $title,
            "post_status" => $this->Status($cache),
            "comment_status" => "close"
        ];
        if ($parent > 0) {
            $datas['post_parent'] = $parent;
        }
        $id = $this->WpId($key, $type);
        if ($id > 0) {
            $datas['ID'] = $id;
            $datas['post_date'] = $datas['post_date_gmt'] = date("Y-m-d H:i:s");
            if (wp_update_post($datas) !== $id) { return 0; }
            $meta = $this->Meta($id, $type, true);
            if (gettype($meta) === gettype($cache) && method_exists($cache, 'Merge')) {
                $cache->Merge($meta);
            }
        } else {
            $id = wp_insert_post($datas);
            $datas['ID'] = $id;
        }
        if (!is_numeric($id)) { return 0; }
        if ($id > 0) {
            $this->Ids[$id] = $this->UpdateMeta($id, $key, $type, $cache);
            $this->Datas[$id] = new WP_Post((object)$datas);
            return $this->Terms($id, $taxes);
        }
        return 0;
    }

    private function Terms(int $id, array $taxes = []): int
    {
        foreach ($taxes as $type => $terms) {
            if (strlen($type) > 0 && is_array($terms) && !empty($terms)) {
                wp_set_object_terms($id, $terms, $type);
            }
        }
        return $id;
    }

    private function ServiceCheck(Service $obj, array $datas = ['price']): array
    {
        $errors = []; $warns = []; $bol = false;
        foreach ($datas as $data) {
            if ($data === 'price') {
                $price = $obj->GetPrice();
                if (strlen($price) <= 0) {
                    $errors[] = 'Valid price is needed.';
                    $bol = true;
                }
                /*rp*/
                /*if (strlen($price) <= 0 || strtolower(substr($price, 0, 2)) !== 'rp') {
                    $errors[] = 'Valid price is needed.';
                    $bol = true;
                }*/
            } else if ($data === 'location') {
                $locations = $obj->GetLocation();
                $category = Plugin::Get('category');
                foreach ($locations as $l => $location) {
                    if (is_null($category->BySlug($location, 'toristy-location'))) {
                        $warns[] = "Valid $l is needed";
                        $bol = true;
                    }
                }
                if (empty($locations) && empty($warns)) {
                    $warns[] = "Valid country and city are needed";
                    $bol = true;
                }
            }
        }
        return [$bol, $warns, $errors];
    }

    private function ProviderCheck(int $id, $type, array $datas = ['count']): array
    {
        $errors = []; $warns = []; $count = 0; $bol = false;
        foreach ($datas as $data) {
            if ($data === 'count') {
                global $wpdb;
                $query = $wpdb->prepare("
                SELECT COUNT(ID) FROM {$wpdb->posts} 
                WHERE post_type = '%s' AND post_parent = '%s'
                ", $type, $id);
                $count = $wpdb->get_var( $query );;
                if ($count <= 0) {
                    $errors[] = 'No Services Found!';
                    $bol = true;
                }
            }
        }
        return [$bol, $warns, $errors, $count];
    }

    private function Status(object $obj): string
    {
        if ($obj instanceof Service) {
            list($bol) = $this->ServiceCheck($obj);
            if ($bol) {
                return 'pending';
            }
            return 'publish';
        }
        return 'publish';
    }

    private function UpdateMeta(int $id, string $key, string $type, ?object $meta): string
    {
        $name = $this->Prefix($key, $type);
        if ($id > 0) {
            if (isset($meta)) {
                update_post_meta($id, $name, $meta);
                $this->Metas[$id] = $meta;
            }
        }
        return $name;
    }

    public function Meta(int $id, string $type, bool $fetch = false): ?object
    {
        if ($id > 0) {
            $meta = (array_key_exists($id, $this->Metas)) ? $this->Metas[$id] : null;
            if (!isset($meta)) {
                $key = $this->TyId($id);
                if (strlen($key) <= 0) { return null; }
                $name = $this->Prefix($key, $type);
                $meta = get_post_meta($id, $name, true);
                if (is_string($meta)) { return null; }
                if (isset($meta) && is_object($meta)) {
                    $this->Metas[$id] = $meta;
                }
            }
            $meta = (isset($meta) && $meta instanceof Service && $fetch) ? $this->Fetch($id, $meta) : $meta;
            return (is_object($meta)) ? $meta : null;
        }
        return null;
    }

    private function Fetch(int $id, Service $service) : Service
    {
        $api = (string)Option::Get("toristy_api_key", "", true);
        if (!is_single($id) || strlen($api) <= 0) {
            return $service;
        }
        $process = new Process($api, '');
        $key = $process->Service($service);
        if (strlen($key) > 0) {
            $this->UpdateMeta($id, $key, 'toristy-service', $service);
        }
        return $service;
    }

    private function Additional(int $id, Process $process): void
    {
        if ($id > 0) {
            $type = 'toristy-provider';
            $provider = $this->Meta($id, $type);
            if ($provider instanceof Provider) {
                $key = $process->Provider($provider);
                if (strlen($key) > 0) {
                    $this->UpdateMeta($id, $key, $type, $provider);
                }
            }
        }
    }

    public function GetSkin(?WP_Post $post): string
    {
        if (!$post instanceof  WP_Post) { return ''; }
        $meta = $this->Meta($post->ID, $post->post_type);
        if (isset($meta) && property_exists($meta, 'skin')) {
            return $meta->skin;
        }
        return '';
    }

    public function Main(string &$title, string &$slug, bool $hide = false, bool $remove = false): WP_Post
    {
        $id = (int)Option::Get("toristy-main-page-id", 0, true);
        $post = null;

        if ($id > 0) {
            $post = get_post($id);
            if ($post instanceof WP_Post) {
                if ($remove) {
                    wp_delete_post($post->ID, true);
                    return $post;
                }
                $slug = $post->post_name;
                $title = $post->post_title;
                if (!$hide && $post->post_status !== 'publish') {
                    $post->post_status = 'publish';
                    wp_update_post($post);
                } else if ($hide) {
                    $post->post_status = 'trash';
                    wp_update_post($post);
                }
                return $post;
            }
        }
        if (!$remove) {
            $datas = [
                "post_type" => 'page',
                "post_name" => $slug,
                "post_title" => $title,
                "post_status" => 'publish',
                "comment_status" => "close"
            ];
            if ($id > 0 && !$post instanceof WP_Post) {
                $datas['import_id'] = $id;
            }
            $id = wp_insert_post($datas);
            if (is_numeric($id) && $id > 0) {
                Option::Set("toristy-main-page-id", $id, true);
                $name = "page__$id";
                update_post_meta($id, $name, (object)[
                    'id' => $id, 'skin' => 'toristy-page']);
                update_post_meta( $id, '_wp_page_template', 'page-blank.php' );
                return get_post($id);
            }
        }
        return $post;
    }

    public function Widget(Service $service, string $action = ''): string
    {
        $id = $service->GetId(); $line = $service->lineOfBusinessId;
        $id = (int)$id;
        $line = (int)$line;
        $data = $this->GetSettings($id, $line, $action);
        return "<div id='toristyiframe-responsive$id'></div>
            <script async src='https://embed.toristy.com/embed.js?$data' charset='utf-8'></script>";
    }

    private function GetSettings($id, $busId, $action = ""): string
    {
        $key = (string)Option::Get("toristy_api_key", "", true);
        $temp = [
            'es' => '%23toristyiframe-responsive'.$id,
            'ct' => 'toolonly',
            'pc' => '53777a',
            'et' => 'service',
            'serviceid' => $id,
            'fontsize' => '15',
            'lang' => 'en',
            'font' => 'kanit',
            'hfont' => 'lato',
            'apikey' => $key
        ];
        if (isset($busId) && $busId !== '') {
            $temp['businessid'] = $busId;
        }
        $values = (array)Option::Get("toristy_setting_options/widget", [], true);
        $values = array_merge($temp, $values);
        if (isset($action) && $action !== "")
        { $values["ct"] = $action; }
        $values['serviceid'] = "$id";
        $values['apikey'] = $key;
        $data = [];
        foreach ($values as $key => $val)
        {
            if (strlen(trim($val)) === 0) { continue; }
            $data[] = "$key=$val";
        }
        return implode("&", $data);
    }

    public function Content($content): string
    {
        return $content;
    }

    public function Count(string $type, string $status = 'publish'): int
    {
        if (in_array($type, $this->Types)) {
            global $wpdb;
            $query = $wpdb->prepare("
                SELECT COUNT(ID) FROM {$wpdb->posts} 
                WHERE post_type = '%s' AND post_status = '%s'
                ", $type, $status);
            return $wpdb->get_var( $query );
        }
        return 0;
    }

    public function Clean()
    {
        global $wpdb;
        foreach ($this->Types as $type) {
            $wpdb->query(
                $wpdb->prepare("
                DELETE p,pm,pt
                FROM {$wpdb->posts} AS p
                LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.ID
                LEFT JOIN {$wpdb->term_relationships} AS pt ON pt.object_id = p.ID
                WHERE p.post_type = %s AND p.post_status = %s
                ", $type, 'trash')
            );
        }
    }

    /**
     * Put all leftover into trash after re-sync.
     * @param string $date
     * @return bool
     */
    public function Trash(string $date): bool
    {
        if (strlen($date) > 0) {
            global $wpdb;
            foreach ($this->Types as $type) {
                $wpdb->query(
                    $wpdb->prepare("
                UPDATE {$wpdb->posts} SET post_status=%s
                WHERE post_type = %s AND post_date <= %s
                ", 'trash', $type, $date)
                );
            }
        }
        return false;
    }

    public function Wipe(): bool
    {
        global $wpdb;
        foreach ($this->Types as $type) {
            $wpdb->query(
                $wpdb->prepare("
                DELETE p,pm,pt
                FROM {$wpdb->posts} AS p
                LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.ID
                LEFT JOIN {$wpdb->term_relationships} AS pt ON pt.object_id = p.ID
                WHERE p.post_type = %s
                ", $type)
            );
        }
        return true;
    }
}