<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Helpers;


use Exception;
use Toristy\Cores\Option;
use Toristy\Cores\Plugin;

abstract class Cron
{
    /**
     * @var string
     */
    protected $Token;
    /**
     * @var string
     */
    protected $Key;

    /**
     * @var bool
     */
    protected $Running;
    /**
     * @var int
     */
    protected $Time = 0;
    /**
     * @var int
     */
    protected $Count;
    /**
     * @var string
     */
    protected $Name;

    protected $RunName = 'cron-running';

    protected $CleanName = 'cron-leftover';

    protected $WorkName = 'cron-working';

    protected function __construct(string $key)
    {
        $this->Key = $key;
        $this->Token = (string)Option::Get("toristy_api_key", "", true);
        Hook::Add('cron-1','wp_ajax_'.$this->Key, [$this, 'Run']);
        Hook::Add('cron-2','wp_ajax_nopriv_'.$this->Key, [$this, 'Run']);
    }

    /**
     * @param string $name
     * @param int $num
     * @return bool
     */
    protected function Update(string $name, int $num): bool
    {
        if (strlen($name) > 0) {
            return Option::Set($name, $num);
        }
        return false;
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function Clear(string $name) : bool
    {
        if (strlen($name) > 0) {
            return Option::Remove($name);
        }
        return false;
    }

    protected function GetDate(?int $stamp = null): string
    {
        return Plugin::GetDate($stamp);
    }

    protected function GetStamp(string $date) : int
    {
        return strtotime($date);
    }

    private function Post()
    {
        $args = array(
            'timeout'   => 0.01,
            'blocking'  => false,
            'body'      => [],
            'cookies'   => $_COOKIE,
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
        );
        return apply_filters( $this->Key . '_post_args', $args );
    }

    private function Args()
    {
        $args = [
            'action' => $this->Key,
            'nonce'  => wp_create_nonce( $this->Key )
        ];
        return apply_filters( $this->Key . '_query_args', $args );
    }

    private function Url()
    {
        $url = admin_url( 'admin-ajax.php' );

        /**
         * Filters the post arguments used during an async request.
         *
         * @param string $url
         */
        return apply_filters( $this->Key . '_query_url', $url );
    }

    public function Dispatch()
    {
        $url  = add_query_arg( $this->Args(), $this->Url() );
        $args = $this->Post();
        return wp_remote_post(esc_url_raw($url), $args);
    }

    protected function IsRunning(): bool
    {
        try {
            $num = (int)Option::Get($this->RunName, 0);
            if ($num > 0) {
                return true;
            }
        } catch (Exception $e) {}
        return false;
    }

    public function Run(): void
    {
        session_write_close();
        check_ajax_referer($this->Key, 'nonce');
        $diff = $this->GetStamp($this->GetDate()) >= $this->Time;
        if (!$this->IsRunning() && $diff && strlen($this->Token) > 0) {;
            $this->Update($this->RunName, 1);
            if ($this->IsRunning()) {
                if (!(bool)Option::Get($this->WorkName, false)) {
                    Option::Set($this->WorkName, true);
                    Option::Set($this->CleanName, $this->GetDate());
                }
                $this->Process();
            }
        }
        $date = (string)Option::Get($this->CleanName, '');
        if (!$this->IsRunning() && strlen($date) > 0 && !$diff) {
            Plugin::Get('page')->Trash($date);
            Plugin::Get('category')->Trash($date);
            Option::Set($this->WorkName, false);
            Option::Set($this->CleanName, '');
        }
        wp_die();
    }

    protected abstract function Process(): bool;

    public function Can(): bool
    {
        return !$this->IsRunning() && $this->GetStamp($this->GetDate()) >= $this->Time;
    }
}