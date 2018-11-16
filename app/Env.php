<?php
/**
 * Created by PhpStorm.
 * User: Marat
 * Date: 14.11.2018
 * Time: 20:39
 */

namespace App;

/**
 * Классдля работы с переменными окружения
 * Class Env
 * @package App
 */
class Env
{
    const ENV_PATH = '/../.env';
    protected static $instance = null;

    protected $env = null;

    /**
     * Env constructor.
     */
    protected function __construct()
    {
        $this->env = parse_ini_file(__DIR__.self::ENV_PATH);
    }

    /**
     * pattern Singleton
     * @return Env
     */
    public static function getInstance():self
    {
        if(!isset(static::$instance)){
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get(string $name):?string
    {
        return $this->env[$name];
    }
    
}