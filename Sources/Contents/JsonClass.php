<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Contents;


trait JsonClass
{
    private $Skips = [];

    private $Maps = [];

    private function Lang() : string
    {
        return get_locale();
    }

    private function GetSkips(): array
    {
        return array_merge(['maps', 'skips'], $this->Skips);
    }

    private function NotNumKeys(array $arr): bool
    {
        return count(array_filter(array_keys($arr), 'is_string')) > 0;
    }

    public function Serialize(): array
    {
        $arrs = json_decode(json_encode($this), true);
        $datas = [];
        foreach ($arrs as $key => $val) {
            $skip = strtolower((string)$key);
            if (!in_array($skip, $this->GetSkips(), true)) {
                $datas[$key] = $val;
            }
        }
        return $datas;
    }

    private function Deserialize($content): void
    {
        $objs = (is_string($content)) ? json_decode($content, true) : $content;
        if (!is_array($objs) || empty($objs)) {
            return;
        }
        $maps = array_change_key_case($this->Maps, CASE_LOWER);
        foreach ($objs as $key => $val) {
            if (property_exists($this, $key) && !is_null($val)) {
                $n = sanitize_title_with_dashes($key);
                $class = isset($maps[$n]) ? $maps[$n] : '';
                //Plugin::Debug([$class, $maps, $n], true);
                if (strlen($class) > 0) {
                    if (!$this->NotNumKeys($val)) {
                        $this->{$key} = [];
                        foreach ($val as $v) {
                            $this->{$key}[] = new $class($v);
                        }
                        continue;
                    }
                    $this->{$key} = new $class($val);
                } else {
                    $this->{$key} = $val;
                }
            }
        }
    }

    public function Merge($objs) : void
    {
        if (gettype($this) !== gettype($objs)) {
            return;
        }
        $keys = $this->GetVars($this);
        foreach ($keys as $key) {
            if (property_exists($this, $key)) {
                $one = $this->{$key};
                $two = (property_exists($objs, $key)) ? $objs->{$key} : null;
                $this->{$key} = $this->Update($one, $two);
            }
        }
    }

    private function Update($datas, $temps)
    {
        $data = gettype($datas); $temp = gettype($temps);
        if ($data === 'NULL' || $temp == 'NULL') {
            return ($data === 'NULL') ? $temps : $datas;
        } elseif ($data !== $temp) {
            return $temps;
        } elseif ($data === 'string' && $temp === 'string') {
            return ($datas !== $temps) ? $temps : $datas;
        } elseif ($data === 'integer' && $temp === 'integer') {
            return ($datas !== $temps) ? $temps : $datas;
        } elseif ($data === 'boolean' && $temp === 'boolean') {
            return ($datas !== $temps) ? $temps : $datas;
        } elseif ($data === 'array' && $temp === 'array') {
            return $this->Arr($datas, $temps);
        } elseif ($data === 'object' && $temp === 'object') {
            return $this->Obj($datas, $temps);
        }
        return $datas;
    }

    private function Arr(array $datas, array $temps): array
    {
        $keys = $this->GetVars($temps);
        //var_dump($datas);
        foreach ($keys as $key) {
            $one = (array_key_exists($key, $datas)) ? $datas[$key] : null;
            $two = (array_key_exists($key, $temps)) ? $temps[$key] : null;
            $datas[$key] = $this->Update($one, $two);
        }
        return $datas;
    }

    private function Obj(object $datas, object $temps): object
    {
        $keys = $this->GetVars($temps);
        $isClass = is_a($datas, get_class($datas), false);
        foreach ($keys as $key) {
            if ($isClass && !property_exists($datas, $key)) {
                continue;
            }
            $one = (property_exists($datas, $key)) ? $datas->{$key} : null;
            $two = (property_exists($temps, $key)) ? $temps->{$key} : null;
            $datas->{$key} = $this->Update($one, $two);
        }
        return $datas;
    }

    private function GetVars($obj): array
    {
        $vars = [];
        foreach ($obj as $key => $val) {
            if (in_array(strtolower($key), $this->GetSkips())) {
                continue;
            }
            $vars[] = $key;
        }
        return $vars;
    }
}