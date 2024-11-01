<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;

use Exception;
use Toristy\Helpers\Crons\DataCron;
use Toristy\Helpers\Crons\SyncCron;
use Toristy\Helpers\Domain;
use Toristy\Helpers\Hook;
use WP_Post;

class Plugin
{
    /**
     * @var Plugin
     */
    private static $Instance;

    private static $Instances = [];

    private static $Abstracts;

    private static $Customs = [
        'customs' => [
            "toristy-service" => [
                'plural' => 'Services', 'singular' => 'Service', 'tags' => ["toristy-category", "toristy-location", "toristy-type"]
            ],
            "toristy-provider" => [
                'plural' => 'Providers', 'singular' => 'Provider', 'tags' => []
            ]
        ],
        'taxes' => [
            'toristy-category' => [
                'plural' => 'Categories', 'singular' => 'Category', 'for' => 'toristy-service'
            ],
            'toristy-location' => [
                'plural' => 'Locations', 'singular' => 'Location', 'for' => 'toristy-service'
            ],
            'toristy-type' => [
                'plural' => 'Types', 'singular' => 'Type', 'for' => 'toristy-service'
            ]
        ]
    ];

    /**
     * @var WP_Post
     */
    private static $Post;
    /**
     * @var string
     */
    private static $Slug = 'toristy';
    /**
     * @var string
     */
    private static $Name = 'Toristy';
    /**
     * @var Page
     */
    private static $Page;
    /**
     * @var Category
     */
    private static $Category;
    /**
     * @var SyncCron | DataCron
     */
    private $Cron;
    /**
     * @var array
     */
    private $Widgets;

    private function __construct()
    {
        Hook::Add('plugin-1', 'init', [$this, "Populate"], 0);
        Domain::Load();
        $this->Abstracts();
        $this->Sync();
        $this->Cron();
        $this->Codes();
        Hook::Run();
    }

    private function Codes()
    {
        /*$this->Codes = [
            new TagCode(),
            new FamousCode(),
            new SearchCode()
        ];*/
        $this->Widgets = [
            //'service-calender' => new CalenderWidget()
        ];
    }

    private function Cron()
    {
        $name = 'toristy-caches';
        $cron = new DataCron($name);
        if ($cron->IsEmpty()) {
            $cron = new SyncCron($name);
        }
        self::$Instances['cron'] = $cron;
    }

    private function Test(): void
    {
    }

    public static function Debug($datas, bool $kill = false): void
    {
        echo '<pre>' . print_r($datas, true) . '</pre>';
        if ($kill) {
            exit('ended');
        }
    }

    public static function GetPage(): Wp_Post
    {
        return self::$Post;
    }

    public static function Job(bool $bol = false, int $num = 0)
    {
        if ($bol) {
            self::$Page->Clean();
            self::$Category->Clean();
        } else {
            Option::Set('toristy-caches', [], true);
            Option::ClearStartWith('cron-');
        }
        if ($num === 2) {
            Option::Set('cron-time', strtotime('+12hours'));
        }
    }

    public function Populate()
    {
        $this->Page();
    }

    /**
     * Create and return instance of this class.
     */
    public static function Init(): Plugin
    {
        if (!isset(self::$Instance)) {
            self::$Instance = new Plugin();
        }

        return self::$Instance;
    }

    private function Page(): void
    {
        self::$Post = self::$Page->Main(self::$Name, self::$Slug);
        self::$Instances['custom'] = new Custom(self::Get('menu'));
    }

    public static function GetPageSlug(): string
    {
        return self::$Slug;
    }

    /**
     * @return string
     */
    public static function GetName(): string
    {
        return self::$Name;
    }

    private function Abstracts() {
        $abstracts = [
            'admin' => ['name' => Admin::class, 'params' => ['settings', 'menu'], 'cache' => true, 'auto' => true],
            'skin' => ['name' => Skin::class, 'params' => ['page', 'category'], 'cache' => true, 'auto' => true],
            'category' => ['name' => Category::class, 'params' => [], 'cache' => true, 'auto' => true],
            'page' => ['name' => Page::class, 'params' => ['menu'], 'cache' => true, 'auto' => true],
            'blocks' => ['name' => Block::class, 'params' => [], 'cache' => true, 'auto' => true],
            'settings' => ['name' => Settings::class, 'params' => [], 'cache' => true],
            'menu' => ['name' => Menu::class, 'params' => [], 'cache' => true],
            //'process' => ['name' => Process::class, 'params' => ['category', 'page'], 'auto' => true]
        ];
        self::$Abstracts = $abstracts;
        $keys = array_keys($abstracts);
        $page = null;
        foreach ($keys as $key) {
            if (isset($abstracts[$key]['auto']) && $abstracts[$key]['auto']) {
                $temp = &self::Get($key);
                if ($temp instanceof Page) {
                    self::$Page = $temp;
                } elseif ($temp instanceof Category) {
                    self::$Category = $temp;
                }
            }
        }
    }

    public static function &Get(string $name)
    {
        $key = strtolower($name);
        if (isset(self::$Instances[$key])) {
            return self::$Instances[$key];
        }
        if ($key === 'plugin') {
            return self::$Instance;
        }
        $abstract = isset(self::$Abstracts[$key]) ? self::$Abstracts[$key] : null;
        try {
            if (is_array($abstract) && array_key_exists('name', $abstract)) {
                $class = $abstract['name'];
                $params = (isset($abstract['params'])) ? $abstract['params'] : [];
                $cache = (isset($abstract['cache']) && $abstract['cache']);
                $props = [];
                foreach ($params as $param) {
                    $props[] = &self::Get($param);
                }
                $instance = new $class(...$props);
                if (method_exists($instance, 'Prepare')) {
                    $instance->Prepare();
                }
                if ($cache) {
                    self::$Instances[$key] = $instance;
                }
                return $instance;
            }
        } catch (Exception $e) {}
        $null = null;
        return $null;
    }

    public static function Remove(string $name): void
    {
        if (isset(self::$Instances[$name])) {
            unset(self::$Instances[$name]);
        }
    }

    /**
     * @param string $type customs | taxes
     * @param bool $deep true: all with values, false: keys only
     * @return array
     */
    public static function GetCustoms(string $type, bool $deep): array
    {
        if ($deep) {
            return isset(self::$Customs[$type]) ? self::$Customs[$type] : [];
        }
        if (isset(self::$Customs[$type])) {
            return array_keys(self::$Customs[$type]);
        }
        return [];
    }

    /**
     * Activate The plugin callback, setting up plugin from here.
     */
    public static function Activate(): void
    {
        self::Job();
        self::Flush();
    }

    /**
     * Cleanup all temporary data used by plugin.
     */
    public static function Deactivate(): void
    {
        self::$Page->Main(self::$Name, self::$Slug, true);
        self::Flush();
    }

    /**
     * Hard cleanup, remove everything from wordpress.
     */
    public static function Uninstall(): void
    {
        self::$Page->Main(self::$Name, self::$Slug, false, true);
        self::$Category->Wipe();
        self::$Page->Wipe();
        self::Get('admin')->Wipe();
        Option::Remove("toristy-main-page-id", true);
        //Clean all data for this plugin in wp_options.
        Option::Clear();
    }

    private static function Flush()
    {
        flush_rewrite_rules();
    }

    private function Sync(): bool
    {
        $skin = &self::Get('skin');
        new Enqueue($skin, self::$Page, self::$Category);
        return true;
    }

    public static function GetDate(?int $stamp = null): string
    {
        if (is_numeric($stamp) && $stamp !== 0) {
            return date("Y-m-d H:i:s", $stamp);
        }
        return date("Y-m-d H:i:s");
    }

    public static function CutSize($text, $size, $end = '...'): string
    {
        if (strlen($text) > $size && $size > 0) {
            $words = explode(' ', $text);
            $str = "";
            $len = 0;
            $num = strlen($end);
            foreach ($words as $word) {
                if ($len >= ($size - $num)) {
                    break;
                }
                $str = "$str $word";
                $len = strlen($str);
            }
            return "$str$end";
        }
        return $text;
    }

    public static function CutWordSize($text, $size): string
    {
        if ($size > 0) {
            $temps = explode(' ', $text);
            if (count($temps) > $size) {
                $words = [];
                $len = 0;
                foreach ($temps as $temp) {
                    if ($len >= $size) { break; }
                    $words[] = $temp;
                    $len = $len + 1;
                }
                $words[] = '...';
                return implode(' ', $words);
            }
        }
        return $text;
    }

    public static function RemoveCssFromString(string $content) : string
    {
        $replaces = [
            '#<p.*?>(.*?)</p>#i' => '<p>\1</p>',
            '#<span.*?>(.*?)</span>#i' => '<span>\1</span>',
            '#<ol.*?>(.*?)</ol>#i' => '<ol>\1</ol>',
            '#<ul.*?>(.*?)</ul>#i' => '<ul>\1</ul>',
            '#<li.*?>(.*?)</li>#i' => '<li>\1</li>'
        ];
        foreach ($replaces as $key => $str) {
            $content = preg_replace($key, $str, $content);
        }
        return $content;
    }

    public static function AgoTime($date) : string
    {
        if (!isset($date) || strlen($date) <= 0) { return ''; }
        $time = (is_string($date)) ? strtotime($date) : $date;
        $diff = time() - $time;
        $conditions = [
            12 * 30 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute',
            1 => 'second'
        ];
        foreach( $conditions as $secs => $str) {
            $d = $diff / $secs;
            if( $d >= 1 ) {
                $t = round( $d );
                return $t . ' ' . $str . ( $t > 1 ? 's' : '' ) . ' ago';
            }
        }
        return '';
    }

    public function __destruct()
    {
        foreach ($this as $dis) {
            unset($dis);
        }
    }
}