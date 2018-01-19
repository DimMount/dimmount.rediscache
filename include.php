<?php

/**
 * Copyright (c) 2017. Dmitry Kozlov. https://github.com/DimMount
 */
class CPHPCacheRedis implements ICacheBackend
{
    /**
     * @var Redis $obRedis
     */
    private static $obRedis;

    /**
     * @var array массив с хешами base_dir
     */
    private static $basedir_version = [];

    /**
     * @var string соль для ключей кеша
     */
    private $sid = '';

    /**
     * @var mixed Статистика записи кеша
     */
    public $written = 0;

    /**
     * @var mixed Статистика чтения кеша
     */
    public $read = 0;

    /**
     * CPHPCacheRedis constructor.
     */
    public function __construct()
    {
        $this->CPHPCacheRedis();
    }

    /**
     * Инициализация соединения с redis
     */
    public function CPHPCacheRedis()
    {
        if (class_exists('Redis')) {
            try {
                if (!is_object(self::$obRedis)) {
                    $redisIP = (defined('BX_REDIS_IP') ? BX_REDIS_IP : '127.0.0.1');
                    $redisPort = (defined('BX_REDIS_PORT') ? BX_REDIS_PORT : '6379');
                    self::$obRedis = new Redis();
                    self::$obRedis->connect($redisIP, $redisPort);
                }

                if (!defined('BX_REDISCACHE_CONNECTED')) {
                    if (self::$obRedis->ping() === '+PONG') {
                        define('BX_REDISCACHE_CONNECTED', true);
                        register_shutdown_function(['CPHPCacheRedis', 'close']);
                    }
                }

                $this->sid = 'BX';
                if (defined('BX_CACHE_SID')) {
                    $this->sid = BX_CACHE_SID;
                }
            } catch (RedisException $e) {
                \AddMessage2Log($e->getMessage(), 'dimmount.rediscache');
            }
        }
    }

    /**
     * Закрытие соединения с redis
     */
    public function close()
    {
        // В некоторых модулях битрикса обнаружен баг (замечено в webservice)
        // В эпилоге битрикс закрывает соединение с кэшем, а потом снова обращается к кэшу, что вызывает ошибку
        // Поэтому закрытие временно коментируем. По завершении хита соединение с редисом все равно закроется

        // if (defined('BX_REDISCACHE_CONNECTED') && is_object(self::$obRedis)) {
        //   self::$obRedis->close();
        // }
    }

    /**
     * @return bool флаг наличия соединения с redis
     */
    public function IsAvailable()
    {
        return defined('BX_REDISCACHE_CONNECTED');
    }

    /**
     * Очистка кеша
     *
     * @param      $basedir
     * @param bool $initdir
     * @param bool $filename
     *
     * @return bool
     */
    public function clean($basedir, $initdir = false, $filename = false)
    {
        if (is_object(self::$obRedis)) {
            if ($filename) {
                if (!isset(self::$basedir_version[$basedir])) {
                    self::$basedir_version[$basedir] = self::$obRedis->get($this->sid . $basedir);
                }

                if (self::$basedir_version[$basedir] === false || self::$basedir_version[$basedir] === '') {
                    return true;
                }

                if ($initdir) {
                    $initdir_version = self::$obRedis->get(self::$basedir_version[$basedir] . '|' . $initdir);
                    if (!$initdir_version) {
                        return true;
                    }
                } else {
                    $initdir_version = '';
                }

                self::$obRedis->del(self::$basedir_version[$basedir] . '|' . $initdir_version . '|' . $filename);
            } else {
                if ($initdir) {
                    if (!isset(self::$basedir_version[$basedir])) {
                        self::$basedir_version[$basedir] = self::$obRedis->get($this->sid . $basedir);
                    }

                    if (self::$basedir_version[$basedir] === false || self::$basedir_version[$basedir] === '') {
                        return true;
                    }

                    $initdir_version = self::$obRedis->get(self::$basedir_version[$basedir] . '|' . $initdir);
                    if (!$initdir_version) {
                        return true;
                    }

                    $keys = self::$obRedis->keys(self::$basedir_version[$basedir] . '|' . $initdir_version . '|*');
                    self::$obRedis->del($keys);
                    self::$obRedis->del(self::$basedir_version[$basedir] . '|' . $initdir);
                } else {
                    if (!isset(self::$basedir_version[$basedir])) {
                        self::$basedir_version[$basedir] = self::$obRedis->get($this->sid . $basedir);
                    }

                    if (self::$basedir_version[$basedir] === false || self::$basedir_version[$basedir] === '') {
                        return true;
                    }

                    $keys = self::$obRedis->keys(self::$basedir_version[$basedir] . '|*');
                    self::$obRedis->del($keys);
                    self::$obRedis->del($this->sid . $basedir);
                    if (isset(self::$basedir_version[$basedir])) {
                        unset(self::$basedir_version[$basedir]);
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Чтение пары ключ/значение из кеша
     *
     * @param $arAllVars
     * @param $basedir
     * @param $initdir
     * @param $filename
     * @param $TTL
     *
     * @return bool
     */
    public function read(&$arAllVars, $basedir, $initdir, $filename, $TTL)
    {
        if (!isset(self::$basedir_version[$basedir])) {
            self::$basedir_version[$basedir] = self::$obRedis->get($this->sid . $basedir);
        }

        if (self::$basedir_version[$basedir] === false || self::$basedir_version[$basedir] === '') {
            return false;
        }

        if ($initdir) {
            $initdir_version = self::$obRedis->get(self::$basedir_version[$basedir] . '|' . $initdir);
            if (!$initdir_version) {
                return false;
            }
        } else {
            $initdir_version = '';
        }

        $serialAllVars = self::$obRedis->get(self::$basedir_version[$basedir] . '|' . $initdir_version . '|' . $filename);

        if (!$serialAllVars) {
            return false;
        }

        $this->read = strlen($serialAllVars);
        $arAllVars = unserialize($serialAllVars);

        return true;
    }

    /**
     * Запись пары ключ/значение в кеш
     *
     * @param $arAllVars
     * @param $basedir
     * @param $initdir
     * @param $filename
     * @param $TTL
     */
    public function write($arAllVars, $basedir, $initdir, $filename, $TTL)
    {
        if (!isset(self::$basedir_version[$basedir])) {
            self::$basedir_version[$basedir] = self::$obRedis->get($this->sid . $basedir);
        }

        if (!self::$basedir_version[$basedir]) {
            self::$basedir_version[$basedir] = $this->sid . md5(mt_rand());
            self::$obRedis->set($this->sid . $basedir, self::$basedir_version[$basedir]);
        }

        if ($initdir) {
            $initdir_version = self::$obRedis->get(self::$basedir_version[$basedir] . '|' . $initdir);
            if (!$initdir_version) {
                $initdir_version = $this->sid . md5(mt_rand());
                self::$obRedis->set(self::$basedir_version[$basedir] . '|' . $initdir, $initdir_version);
            }
        } else {
            $initdir_version = '';
        }

        $serialAllVars = serialize($arAllVars);
        $this->written = strlen($serialAllVars);

        self::$obRedis->set(self::$basedir_version[$basedir] . '|' . $initdir_version . '|' . $filename,
            $serialAllVars, (int)$TTL);
    }

    /**
     * Унаследовано от интерфейса ICacheBackend всегда возвращает false
     *
     * @param $path
     *
     * @return bool
     */
    public function IsCacheExpired($path)
    {
        return false;
    }
}
