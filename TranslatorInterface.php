<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Translation;

interface TranslatorInterface
{
    /**
     * @param callable $callback
     * @return mixed
     */
    public static function make(\Closure $callback);

    /**
     * @param null $lang
     * @return mixed
     */
    public function locale($lang = null);

    /**
     * @param $fallback
     * @return mixed
     */
    public function setFallback($fallback);

    /**
     * @param      $string
     * @param null $lang
     * @return mixed
     */
    public function get($string, $lang = null);

    /**
     * @param $string
     * @return mixed
     */
    public function has($string);
}