<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Cygnite\Translation\Translator;

if (!function_exists('trans')) {
    /**
     *    trans('Hello, :user', array(':user' => $username));
     *
     * The target language is defined by [Translator::$lang].
     *
     * @uses     Translation::get
     * @param         $key
     * @param   array $replace values to replace in the translated text
     * @param string  $locale
     * @internal param string $string text to translate
     * @internal param string $lang source language
     * @return  string
     */
    function trans($key, array $replace = null, $locale = 'en-us')
    {
        return Translator::make(function ($trans) use ($key, $replace, $locale)
        {
            if ($locale !== $trans->locale()) {
                // The message and target languages are different
                // Get the translation for this message
                $key = $trans->get($key);
            }

            return empty($replace) ? $key : strtr($key, $replace);
        });
    }
}