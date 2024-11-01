<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;


use Toristy\Helpers\Hook;

class Menu
{
    private $Admins = [];

    private $Subs = [];

    public function __construct()
    {
        Hook::Add('menu-1', 'admin_menu', [$this, 'Populate']);
    }

    /**
     * Generate the Admin Pages
     */
    public function Populate(): void
    {
        $pages = $this->Admins;
        foreach ($pages as $page) {
            add_menu_page(
                $page["page_title"],
                $page["menu_title"],
                $page["capability"],
                $page["menu_slug"],
                $page["callback"],
                $page["icon_url"],
                $page["position"]
            );
        }
        $subs = $this->Subs;
        foreach ($subs as $sub) {
            add_submenu_page(
                $sub["parent_slug"],
                $sub["page_title"],
                $sub["menu_title"],
                $sub["capability"],
                $sub["menu_slug"],
                $sub["callback"]
            );
        }
    }

    /**
     * Admin page can be created easily by passing in all those page information.
     *
     * @param  string  $title
     * @param  string  $name
     * @param  string  $slug
     * @param  array  $callback
     * @param  int  $position
     * @param  string  $icon
     * @param  string  $parentSlug
     * @param  string  $capability
     */
    public function Add(string $title, string $name, string $slug, array $callback, int $position, string $icon = "", string $parentSlug = "", string $capability = "manage_options"): void {
        $bol = ($parentSlug === "");
        $page  = [
            "page_title"  => $title,
            "menu_title"  => $name,
            "capability"  => $capability,
            "menu_slug"   => $slug,
            "callback"    => $callback,
            "icon_url"    => $icon,
            "position"    => $position,
            "parent_slug" => ($bol) ? null : $parentSlug
        ];
        if ($bol) {
            $this->Admins[] = $page;
        } else {
            if ($page['menu_slug'] === $page['parent_slug']) {
                array_unshift($this->Subs, $page);
            } else {
                $this->Subs[] = $page;
            }

        }
    }
}