<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;


use Toristy\Helpers\Hook;

class Custom
{
    private $Posts = [];

    private $Taxonomies = [];

    public function __construct(Menu $menu)
    {
        $posts = Plugin::GetCustoms('customs', true);
        $taxes = Plugin::GetCustoms('taxes', true);
        $this->Posts = array_keys($posts);
        $this->Taxonomies = array_keys($taxes);
        Hook::Add('custom-1',"init", [$this, "Populate"], 2);
        $this->Populate($posts, $taxes, $menu);
    }

    private function Menu(Menu $menu, string $name, string $path)
    {
        $slug = Admin::$Slug;
        $menu->Add(
            $name,
            $name,
            $path,
            [],
            0,
            "",
            $slug
        );
    }

    public function Populate(array $posts, array $taxes, Menu $menu)
    {
        $slug = Plugin::GetPageSlug();
        foreach ($posts as $type => $post) {
            $plural = (isset($post['plural'])) ? $post['plural'] : '';
            $singular = (isset($post['singular'])) ? $post['singular'] : '';
            $tags = (isset($post['tags'])) ? $post['tags'] : '';
            if ($plural === '' || $singular === '') { continue; }
            $link = ($type === 'toristy-service') ? $slug.'/'.$slug[0] : "$slug/".strtolower($singular);
            $this->Post(
                $this->Label(
                    $plural,
                    $singular,
                    $plural
                ),
                $type,
                $link,
                false,
                $tags,
                "post",
                "",
                true,
                true,
                false,
                true,
                false,
                ['create_posts' => false],
                true
            );
            $this->Menu($menu, $plural, "edit.php?post_type=$type");
        }
        $terms = [
            'manage_terms' => 'edit_posts',
            'delete_terms' => '',
            'assign_terms' => 'edit_posts'
        ];
        foreach ($taxes as $type => $tax) {
            $for = (isset($tax['for'])) ? $tax['for'] : '';
            $plural = (isset($tax['plural'])) ? $tax['plural'] : '';
            $singular = (isset($tax['singular'])) ? $tax['singular'] : '';
            if ($plural === '' || $singular === '') { continue; }
            $filter = ($type === 'toristy-type') ? "$slug/filter%/cat%" : "$slug/filter%";
            $this->Taxonomy(
                $type,
                $for,
                true,
                $this->Label($plural, $singular, $plural),
                true,
                $filter,
                true,
                true,
                $terms
            );
            $this->Menu($menu, $plural, "edit-tags.php?taxonomy=$type");
        }
    }

    /**
     * Add post/page type
     *
     * @param  array  $labels
     * @param  string  $type  post or page
     * @param  string  $slug  custom post type url here
     * @param  bool  $front  to allow slug to be prepend in front.
     * @param  array  $taxes
     * @param  string  $capability
     * @param  string  $description
     * @param  bool  $show  show in admin bar
     * @param  bool  $menu  show in nav menus
     * @param  mixed  $admin  show in admin
     * @param  bool  $view  make public: true
     * @param  bool  $archive
     * @param  array  $capabilities
     * @param  bool  $meta  map_meta_cap
     * @param  int  $position
     */
    private function Post(array $labels, string $type, string $slug, bool $front, array $taxes = ['category', 'post_tag'], string $capability = "post", string $description = "", bool $show = false, bool $menu = true, $admin = "", bool $view = true, bool $archive = true, array $capabilities = [], bool $meta = false, int $position = 5): void {
        if ($type === "") {
            return;
        }
        register_post_type($type, [
            "type"                => $type,
            'labels'              => $labels,
            'label'               => $labels['name'],
            'description'         => $description,
            'supports'            => ['title', 'editor'],//,['title', 'editor', 'custom-fields', 'thumbnail', 'page-attributes'],
            'show_in_rest'        => true,
            'taxonomies'          => $taxes,
            'hierarchical'        => false,
            'public'              => $view,
            'show_ui'             => true,
            'show_in_menu'        => $admin,
            'menu_position'       => $position,
            'show_in_admin_bar'   => $show,
            'show_in_nav_menus'   => $menu,
            'can_export'          => false,
            'has_archive'         => $archive,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => $capability,
            'capabilities'        => $capabilities,
            'map_meta_cap'        => $meta,
            "rewrite"      => [
                "slug"       => $slug,
                "with_front" => $front
            ]
        ]);
    }

    /**
     * Create Post type label.
     *
     * @param  string  $plural  plural name
     * @param  string  $singular  name
     * @param  string  $menu  admin menu name
     *
     * @return array
     */
    private function Label(string $plural, string $singular, string $menu): array {
        return [
            'name'                  => $plural,
            'singular_name'         => $singular,
            'menu_name'             => $menu,
            'name_admin_bar'        => $singular,
            'archives'              => "$singular Archives",
            'attributes'            => "$singular Attributes",
            'parent_item_colon'     => "Parent $singular",
            'all_items'             => "All $plural",
            'add_new_item'          => "Add New $singular",
            'add_new'               => "Add New",
            'new_item'              => "New $singular",
            'edit_item'             => "Edit $singular",
            'update_item'           => "Update $singular",
            'view_item'             => "View $singular",
            'view_items'            => "New $plural",
            'search_items'          => "Search $plural",
            'not_found'             => "No $singular found",
            'not_found_in_trash'    => "No $singular found in trash",
            'featured_image'        => "Featured Image",
            'set_featured_image'    => "Set Featured Image",
            'remove_featured_image' => "Remove Featured Image",
            'use_featured_image'    => "Use Featured Image",
            'insert_into_item'      => "Insert into $singular",
            'uploaded_to_this_item' => "Upload to $singular",
            'items_list'            => "$plural List",
            'items_list_navigation' => "$plural List Navigation",
            'filter_items_list'     => "Filter $plural List"
        ];
    }

    /**
     * @param  string  $name  taxonomy name
     * @param  string  $type  post-type
     * @param  bool  $hera  hierarchical
     * @param  mixed  $label  string or array or labels. from Custom->labels();
     * @param  bool  $var
     * @param  string  $slug  This controls the base slug that will display before each term
     * @param  bool  $front  Don't display the category base before
     * * @param  bool  $view  make public: true
     * @param  array  $capabilities
     */
    private function Taxonomy(string $name, string $type, bool $hera, $label, bool $var,
                           string $slug, bool $front, bool $view, array $capabilities = []): void
    {
        $data = [
            'capabilities' => $capabilities,
            "hierarchical" => $hera,
            'show_in_rest' => true,
            "query_var"    => $var,
            "rewrite"      => [
                "slug"       => $slug,
                "hierarchical" => $hera,
                "with_front" => $front
            ],
            "show_in_menu" => true,
            'show_in_nav_menus' => true,
            'show_admin_column' => true,
            'public'    => $view,
            "show_ui" => true,
            "publicly_queryable" => true
        ];
        if (isset($label)) {
            if (is_array($label))
            { $data["labels"] = $label; }
            else { $data["label"] = $label; }
        }
        register_taxonomy(
            $name,
            $type,
            $data
        );
    }
}