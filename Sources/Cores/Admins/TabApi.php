<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores\Admins;

use Toristy\Cores\Option;
use Toristy\Cores\Plugin;
use Toristy\Cores\Settings;
use Toristy\Helpers\Hook;

class TabApi extends AdminTab
{
    private $Notes = [];

    public function __construct(Settings $settings, string $key)
    {
        $this->Title = 'Api Options';
        $this->Bol = ((bool)Option::Get('cron-working', false));
        parent::__construct($settings, 'toristy_api_key', '', 'api', $key);
    }

    public function Load($args)
    {
        $name = $args['name'];
        $note = $args['note'];
        if ($name === 'api') {
            $value = $this->Options;
            $stop = ($this->Bol) ? ' disabled' : '';
            echo "<input type='text' class='regular-text'$stop name='$this->Option' value='$value' placeholder='API key here!'/>";
        } else {
            echo implode('', $this->Extras);
        }
        echo "<p>$note</p>";
    }

    public function Section(string $title = '')
    {
        echo ($title === 'Toristy API') ? "<p>Write or paste the API key from Toristy below!</p>" :
            $this->Notes[$title];
    }

    public function Loadx($args)
    {
        $value = $this->Options;
        $stop = ($this->Bol) ? ' disabled' : '';
        echo "<input type='text' class='regular-text'$stop name='$this->Option' value='$value' placeholder='API key here!'/>";
        echo "
        <div>
            <p>Write or paste the API key from Toristy above!</p>
            <p style='border-color: red; color: red'>While syncing api key can not be change.</p>
        </div>
        ";
    }

    protected function Populate()
    {
        list ($sync, $cache) = $this->Extras();
        $this->Add('sync', 'Sync Control', [
            "name" => 'sync',
            "note" => "$sync"
        ], 'Sync Options');

        $this->Add('cache', 'Cache Control', [
            "name" => 'cache',
            "note" => "$cache"
        ], 'Cache Options');

        $this->Add('api', 'API key', [
            "name" => 'api',
            "note" => "<p style='border-color: red; color: red'>While syncing api key can not be change.</p>"
        ], 'Toristy API');
    }

    protected function Extra(): void
    {

    }

    protected function Extrax(): void
    {
        $bol = strlen($this->Options) <= 0;
        Hook::Add('admin-tab-1','wp_ajax_toristy_sync', [$this, 'Sync']);
        $count = Plugin::Get('page')->Count('toristy-service', 'trash');
        $count1 = Plugin::Get('page')->Count('toristy-provider', 'trash');
        $count2 = Plugin::Get('category')->Count('toristy-category', 'trash');
        $count3 = Plugin::Get('category')->Count('toristy-type', 'trash');
        $count4 = Plugin::Get('category')->Count('toristy-location', 'trash');
        $allow = ($count <= 0 && $count1 <= 0 && $count2 <= 0 && $count3 <= 0 && $count4 <= 0);
        $status = ($this->Bol) ? '<span style="color: green">Activated</span>' : '<span style="color: red">Not Active</span>';
        $date = Option::Get('cron-time', '');
        $info = (!$this->Bol && strlen($date) > 0) ? "<p>Next Sync at: ".date('d/m/Y H:i:s', $date)."</p>" : '';
        $sync = ($this->Bol || $bol) ? "<span class='button'>Sync: $status</span>" :
            "<a class='button toristy-sync sync' data-sync='1' href='#'>Sync: $status</a>";
        $cache = ($this->Bol || $bol || $allow) ? "<span class='button'>Clear Cache ($count)</span>" :
            "<a class='button toristy-sync cache' data-sync='2' href='#'>Clear Cache</a>";
        $this->Extras = ["<div class='toristy-syncs'>",
            "<div><div>
                <p></p>
                <p>Re-sync content into WordPress.</p>
                $info
                $sync
                <p></p>
            </div>",
            "<div>
                <p>Remove all trashed items (services: $count, providers: $count1, categories: $count2, types: $count3, locations: $count4).</p>
                $cache
            </div>",
            '</div></div>'];
    }

    private function Extras(): array
    {
        $bol = strlen($this->Options) <= 0;
        Hook::Add('admin-tab-1','wp_ajax_toristy_sync', [$this, 'Sync']);
        $count = Plugin::Get('page')->Count('toristy-service', 'trash');
        $count1 = Plugin::Get('page')->Count('toristy-provider', 'trash');
        $count2 = Plugin::Get('category')->Count('toristy-category', 'trash');
        $count3 = Plugin::Get('category')->Count('toristy-type', 'trash');
        $count4 = Plugin::Get('category')->Count('toristy-location', 'trash');
        $allow = ($count <= 0 && $count1 <= 0 && $count2 <= 0 && $count3 <= 0 && $count4 <= 0);
        $status = ($this->Bol) ? '<span style="color: green">Activated</span>' : '<span style="color: red">Not Active</span>';
        $date = Option::Get('cron-time', '');
        $info = (!$this->Bol && strlen($date) > 0) ? "<p>Next Sync at: ".date('d/m/Y H:i:s', $date)."</p>" : '';
        $sync = ($this->Bol || $bol) ? "<span class='button'>Sync: $status</span>" :
            "<a class='button toristy-sync sync' data-sync='1' href='#'>Sync: $status</a>";
        $cache = ($this->Bol || $bol || $allow) ? "<span class='button'>Clear Cache</span>" :
            "<a class='button toristy-sync cache' data-sync='2' href='#'>Clear Cache</a>";
        $this->Notes = [
            "Sync Options" => "<p>Re-sync content into WordPress.</p>",
            "Cache Options" => "<p>Remove all trashed items (services: $count, providers: $count1, categories: $count2, types: $count3, locations: $count4).</p>"
        ];
        return [
            "<div>$sync</div>$info",
            "<div>$cache</div>"
        ];
        $this->Extras = ["<div class='toristy-syncs'>",
            "<div><div>
                <p></p>
                
                $info
                $sync
                <p></p>
            </div>",
            "<div>
                <p>Remove all trashed items (services: $count, providers: $count1, categories: $count2, types: $count3, locations: $count4).</p>
                $cache
            </div>",
            '</div></div>'];
    }

    public function Sync()
    {
        check_ajax_referer('ajax_nonce', 'nonce');
        $sync = (int)stripslashes($_POST['sync']) ?? '';
        $bol = $sync > 0;
        if ($bol) {
            Plugin::Job($sync === 2);
        }
        wp_send_json_success($bol);
    }
}