<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;


use Exception;
use Toristy\Helpers\Hook;
use WP_Error;
use WP_Term;

class Category
{
    /**
     * @var string
     */
    private static $Name = 'taxonomy-dates';

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
    /**
     * @var array
     */
    private $Dates;

    public function __construct()
    {
        Option::Remove(Category::class);
        $num = 0;
        $this->Types = Plugin::GetCustoms('taxes', false);
        $this->Dates = (array)Option::Get(self::$Name, []);
        foreach ($this->Types as  $tax) {
            ++$num;
            Hook::Add('category-'.$num.'',''.$tax.'_row_actions', [$this, 'Action'], 10, 2);
        }
    }

    public function Action($actions, WP_Term $term)
    {
        if ($term instanceof WP_Term && !empty($actions))
        {
            unset($actions['inline hide-if-no-js'], $actions['trash'], $actions['edit']);
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
                $str = $wpdb->get_var($wpdb->prepare("SELECT meta_key FROM $wpdb->termmeta WHERE term_id = %s", $id));
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
                    $id = (int)$wpdb->get_var($wpdb->prepare("SELECT term_id FROM $wpdb->termmeta WHERE meta_key = %s", $name));
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
     * @return WP_Term|null
     */
    public function TyGet(string $str, string $type): ?WP_Term
    {
        $id = $this->WpId($str, $type);
        if ($id > 0) {
            return $this->WpGet($id);
        }
        return null;
    }

    /**
     * @param int $id wordpress id
     * @return WP_Term|null
     */
    public function WpGet(int $id): ?WP_Term
    {
        if ($id > 0) {
            $data = (array_key_exists($id, $this->Datas)) ? $this->Datas[$id] : null;
            if (!$data instanceof WP_Term) {
                $data = get_term($id);
                if ($data instanceof WP_Term) {
                    $this->Datas[$id] = $data;
                } else { $data = null; }
            }
            return $data;
        }
        return null;
    }

    public function BySlug(string $path, string $type): ?WP_Term
    {
        if ($type === 'toristy-city') {
            $type = 'toristy-location';
        }
        $slug = sanitize_title_with_dashes($path);
        $term = get_term_by('slug', $slug, $type);
        if ($term instanceof WP_Error) {
            return null;
        }
        $term = (object)$term;
        if (property_exists($term, 'term_id')) {
            $meta = $this->Meta($term->term_id, $type);
            if (isset($meta)) {
                return $this->WpGet($term->term_id);
            }
        }
        return null;
    }

    /**
     * @param array $args [total: int, hide_empty: bool, taxonomy: string, orderby: id, order: ASC, exclude: array of ids]
     * @return array
     */
    public function All(array $args = []) : array
    {
        $datas = [];
        if (!array_key_exists('taxonomy', $args) || !in_array($args['taxonomy'], $this->Types)) {
            return $datas;
        }
        $type = $args['taxonomy'];
        $total = 0;
        $excludes = (isset($args['exclude'])) ? $args['exclude'] : [];
        if (isset($args['total']) && (int)$args['total'] > 0) {
            $args['number'] = (int)$args['total'];
            $total = $args['number'];
            unset($args['total']);
        }
        foreach ($this->Datas as $id => $data) {
            if (!in_array($id, $excludes) && $data->taxonomy === $type) {
                $datas[] = $data;
                $excludes[] = $id;
                if ($total > 0 && count($datas) === $total) {
                    return $datas;
                }
            }
        }
        $args['exclude'] = $excludes;
        $terms = get_terms($args);
        if (is_array($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $datas[] = $term;
                if (!array_key_exists($term->term_id, $this->Datas)) {
                    $this->Datas[$term->term_id] = $term;
                }
            }
        }
        return  $datas;
    }

    public function Update(string $key, string $type, string $title, string $slug, object $cache, int $parent = 0): int
    {
        if (strlen($key) <= 0 || strlen($title) <= 0 || strlen($type) <= 0) { return 0; }
        $slug = sanitize_title_with_dashes((strlen($slug) > 0) ? $slug : $title);
        $datas = [
            'title' => $title,
            'slug' => $slug,
            'parent' => $parent
        ];
        $id = $this->WpId($key, $type);
        if ($id > 0) {
            $term = (object)wp_update_term($id, $type, $datas);
        } else {
            $term = (object)wp_insert_term($title, $type, $datas);
        }
        if (is_object($term) && property_exists($term, 'term_id')) {
            if (isset($cache)) {
                if (!is_null($meta = $this->Meta($id, $type))) {
                    if (gettype($meta) === gettype($cache) && method_exists($cache, 'Merge')) {
                        $cache->Merge($meta);
                    }
                }
            }
            $add = false;
            if ($id <= 0) {
                $id = $term->term_id; $add = true;
            }
            $this->Dates[$id] = [
                'date' => Plugin::GetDate(), 'trash' => false, 'type' => $type
            ];
            Option::Set(self::$Name, $this->Dates);
            $this->Ids[$id] = $this->UpdateMeta($id, $key, $type, $cache, $add);
            $this->Datas[$id] = new WP_Term($term);
        }
        return $id;
    }

    private function UpdateMeta(int $id, string $key, string $type, ?object $meta, bool $add = true): string
    {
        $name = $this->Prefix($key, $type);
        if ($id > 0) {
            if (isset($meta)) {
                if ($add) {
                    add_term_meta($id, $name, $meta);
                } else {
                    update_term_meta($id, $name, $meta);
                }
                $this->Metas[$id] = $meta;
            }
        }
        return $name;
    }

    public function Meta(int $id, string $type): ?object
    {
        if ($id > 0) {
            $meta = (array_key_exists($id, $this->Metas)) ? $this->Metas[$id] : null;
            if (!isset($meta) || !is_object($meta)) {
                $key = $this->TyId($id);
                if (strlen($key) > 0) {
                    $meta = get_term_meta($id, $this->Prefix($key, $type), true);
                    if (isset($meta) && is_object($meta)) {
                        $this->Metas[$id] = $meta;
                    }
                }
            }
            return (is_object($meta)) ? $meta : null;
        }
        return null;
    }

    public function Count(string $type, string $status = 'publish'): int
    {
        if (in_array($type, $this->Types)) {
            $ids = [];
            if (!empty($this->Dates)) {
                foreach ($this->Dates as $k => $d) {
                    if ($d['trash'] && $d['type'] === $type) {
                        $ids[] = $k;
                    }
                }
            }
            if ($status === 'publish') {
                $terms = $this->All(['taxonomy' => $type, 'exclude' => $ids]);
                return count($terms);
            }
            return count($ids);
        }
        return 0;
    }

    public function Clean()
    {
        if (!empty($this->Dates)) {
            foreach ($this->Dates as $k => $d) {
                if ($d['trash']) {
                    $ids[] = $k;
                }
            }
            if (!empty($ids)) {
                global $wpdb;
                $num = $wpdb->query(
                    $wpdb->prepare("
                        DELETE tt,t,tm
                        FROM ".$wpdb->term_taxonomy." tt
                        LEFT JOIN ".$wpdb->terms." AS t ON t.term_id = tt.term_id
                        LEFT JOIN ".$wpdb->termmeta." AS tm ON tm.term_id = tt.term_id
                        WHERE tt.term_id IN (%s)
                ", implode(', ', $ids))
                );
                if ($num > 0) {
                    foreach ($ids as $id) {
                        unset($this->Dates[$id]);
                    }
                    Option::Set(self::$Name, $this->Dates);
                }
            }
        }
    }

    /**
     * Put all leftover into trash after re-sync.
     * @param string $date
     * @return bool
     */
    public function Trash(string $date): bool
    {
        $bol = false;
        if (strlen($date) > 0) {
            foreach ($this->Dates as $k => $d) {
                if (strlen($d['date']) > 0 && $d['date'] <= $date) {
                    $this->Dates[$k]['trash'] = $bol = true;
                }
            }
            Option::Set(self::$Name, $this->Dates);
        }
        return $bol;
    }

    public function Wipe(): bool
    {
        global $wpdb;
        foreach ($this->Types as $type) {
            $wpdb->query(
                $wpdb->prepare("
                DELETE tt,t,tm
                FROM ".$wpdb->term_taxonomy." tt
                LEFT JOIN ".$wpdb->terms." AS t ON t.term_id = tt.term_id
                LEFT JOIN ".$wpdb->termmeta." AS tm ON tm.term_id = tt.term_id
                WHERE tt.taxonomy = %s
                ", $type)
            );
            Option::Remove($type.'_children', true);
        }
        return true;
    }
}