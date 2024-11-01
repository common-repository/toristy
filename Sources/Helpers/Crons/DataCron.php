<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Helpers\Crons;


use Toristy\Cores\Option;
use Toristy\Helpers\Cron;
use Toristy\Helpers\Generator;

class DataCron extends Cron
{
    /**
     * @var string
     */
    private $Cache;
    /**
     * @var array
     */
    private $Datas;

    public function __construct(string $cache)
    {
        $this->Cache = $cache;
        $this->Datas = Option::Get($this->Cache, [], true);
        $this->Name = Option::Get('cron-process', '');
        parent::__construct('toristy_data');
    }

    public function IsEmpty() : bool
    {
        return empty($this->Datas);
    }

    public function Can(): bool
    {
        return !empty($this->Datas) && parent::Can();
    }

    protected function Process(): bool
    {
        if (!empty($this->Datas)) {
            $generate = new Generator($this->Datas, $this->CleanName);
            if (method_exists($generate, $this->Name)) {
                if ($generate->{$this->Name}()) {
                    Option::Set($this->Cache, [], true);
                    Option::Set('cron-process', '');
                }
            }
        }
        return $this->Clear($this->RunName);
    }
}