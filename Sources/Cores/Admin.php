<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;

use Toristy\Cores\Admins\AdminTab;
use Toristy\Cores\Admins\TabApi;
use Toristy\Cores\Admins\TabFeature;
use Toristy\Cores\Admins\TabMap;
use Toristy\Cores\Admins\TabOptions;
use Toristy\Cores\Admins\TabPage;
use Toristy\Cores\Admins\TabRecommend;
use Toristy\Cores\Admins\TabServices;
use Toristy\Cores\Admins\TabWidget;
use Toristy\Helpers\Domain;
use Toristy\Helpers\Hook;

class Admin
{
    /**
     * @var array
     */
    private $Names = [];
    /**
     * @var array
     */
    private $Datas = [];
    /**
     * @var array
     */
    private $Tabs = [[
        'about', 'about', 'About',
        "<p><strong>This plugin requires a Toristy account and api key to work.</strong></p>
        <p>Toristy is an In Destination reseller and online software as a service. Our mission is to make tour and activity operators in the experiences sector of travel famous. 
        We do this by selling operator services on the websites of domestic travel networks such as ferries, trains, airlines, hotels (concierges) and local marketplaces, 
        helping operators build a local sales network and simultaneously providing new revenue streams for all concerned. 
        Toristy resells 1000s of services and works with partners such as Google, HotelBeds, Bókun, PrioHub, WeChat and others to create the supply and demand in domestic markets.</p>
        <p>If you're already with Toristy, check out our <a target=\"_blank\" href=\"https://toristy.com/toristy-for-wordpress\">help center</a> and if you don't have an account yet <a target=\"_blank\" href=\"https://www.toristy.com/pricing\">sign up for free</a></p>",
        '', false
    ]];

    public static $Slug = "toristy-settings";

    public function __construct(Settings $settings, Menu $menu)
    {
        Hook::Add('admin-1','updated_option', [$this, 'Options'], 10, 3);
        $this->Tabs($settings);
        $this->Menu($menu);
    }

    private function Tabs(Settings $settings) : void
    {
        $this->Datas = [
            new TabApi($settings, 'one'),
            new TabPage($settings, 'two'),
            new TabServices($settings, 'three'),
            new TabOptions($settings, 'four')
        ];
        $tabs = [];
        foreach ($this->Datas as $tab) {
            if ($tab instanceof AdminTab) {
                $this->Names[] = $tab->GetOptionKey();
                $tabs[] = $tab->GetTabs();
            }
        }
        $this->Tabs = array_merge($tabs, $this->Tabs);
    }

    private function Menu(Menu $menu)
    {
        $menu->Add("Toristy", "Toristy", self::$Slug, [$this, "Index"], 25, "");
        $menu->Add(
            "Toristy",
            "Dashboard",
            self::$Slug,
            [$this, "Index"],
            25,
            "",
            self::$Slug
        );
    }

    public function Index(): void
    {
        echo Domain::RequireBuffer(Domain::Path("pages/admin.php"));
    }

    public function Options(string $option, $older, $newer)
    {
        if (strpos($option, 'toristy') !== false) {
            if ($option === "toristy_api_key") {
                Plugin::Job();
            }
        }
    }

    /**
     * @return array
     */
    public function GetTabs(): array
    {
        return $this->Tabs;
    }

    public function Wipe(): void
    {
        foreach ($this->Names as $name) {
            Option::Remove($name, true);
        }
    }
}