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

/**
 * Class Translator
 *
 * @package Cygnite\Translation
 */
class Translator implements TranslatorInterface
{
    /**
     * @var  string   target language: en, en-us, es-es, zh-cn, etc
     */
    public static $locale = 'en-us';
    /**
     * @var  string  source language: en-us, es-es, zh-cn, etc
     */
    public static $source = 'en-us';
    public static $fallback = 'en';
    public static $rootDir;
    public static $langDir;
    protected $ext = '.php';
    /**
     * @var  array  cache of loaded languages
     */
    protected $cache = [];

    /**
     * Create Translator instance and return
     *
     *  Translator::make(function($trans)
     *  {
     *      $trans->locale('es');
     *  });
     *
     * @param callable $callback
     * @return static
     */
    public static function make(\Closure $callback)
    {
        if ($callback instanceof \Closure) {
            return $callback(new Static());
        }

        return new Static();
    }

    /**
     * Get and set the target language.
     *
     *     // Get the current language
     *     $locale = $trans->locale();
     *
     *     // Change the current language to Spanish
     *     $trans->locale('es');
     *
     * @param   string $locale   new language setting
     * @return  string
     */
    public function locale($locale = null)
    {
        if ($locale) {
            // Normalize the language
            self::$locale = strtolower(str_replace(array(' ', '_'), '-', $locale));
        }

        return self::$locale;
    }

    /**
     * Get the fallback locale being used.
     *
     * @return string
     */
    public function getFallback()
    {
        return static::$fallback;
    }

    /**
     * Set the fallback locale being used.
     *
     * @param  string $fallback
     * @return void
     */
    public function setFallback($fallback)
    {
        static::$fallback = $fallback;

        return $this;
    }

    /**
     * Returns Translator of a string. If no Translator exists, the original
     * string will be returned.
     *
     * trans('Hello, :user', array(':user' => $username));
     * $hello = $trans->get('welcome.Hello friends, my name is :name');
     *
     * @param      $key    to translate
     * @param null $locale target language
     * @return  string
     */
    public function get($key, $locale = null)
    {
        if (!$locale) {
            // Use the global target language
            $locale = $this->locale();
        }

        if (strpos($key, '.') !== false) {
            $exp = explode('.', $key);
            // Load the translation table for this language
            $translator = $this->load($locale . '-' . $exp[0]);
            // Return the translated string if it exists
            return isset($translator[$exp[1]]) ? $translator[$exp[1]] : $key;
        }

        // Load the Translator array for this language
        $translator = $this->load($locale);
        // Return the translated string if it exists
        return isset($translator[$key]) ? $translator[$key] : $key;
    }

    /**
     * Check if language file exists
     *
     * @param      $key
     * @param null $locale
     * @return bool
     */
    public function has($key, $locale = null)
    {
        return $this->get($key, $locale) !== $key;
    }

    /**
     * Set root directory of language files.
     *
     * @param $dir
     * @return $this
     */
    public function setRootDirectory($dir)
    {
        static::$rootDir = $dir;

        return $this;
    }

    /**
     * Get the root directory of language files.
     * If not set system will use default path.
     *
     * @return string
     */
    public function getRootDirectory()
    {
        return isset(static::$rootDir) ? static::$rootDir : null;
    }

    /**
     * Get language directory name, if not set we will return default
     * name
     *
     * @return string
     */
    public function getLangDir()
    {
        return isset(static::$langDir) ? static::$langDir : null;
    }

    /**
     * Set language directory name
     *
     * @param $dir
     * @return $this
     */
    public function setLangDir($dir)
    {
        static::$langDir = $dir;

        return $this;
    }

    /**
     * Set language files extension
     *
     * @param $ext
     * @return $this;
     */
    public function setFileExtension($ext)
    {
        $this->ext = $ext;

        return $this;
    }

    /**
     * Get language file extension
     *
     * @return string
     */
    public function getFileExtension()
    {
        return $this->ext;
    }

    /**
     * Find the language file if exists load it into list
     * else search for fallback locale and load
     *
     * @param $file
     * @return array
     */
    public function findLanguageFile($file)
    {
        // Create a partial path of the filename
        $path = DIRECTORY_SEPARATOR . $file . $this->getFileExtension();

        // Include paths must be searched in reverse
        $paths = array_reverse([$this->getRootDirectory() . $this->getLangDir()]);

        // Array of files that have been found
        $locale = [];
        foreach ($paths as $dir) {

            if (is_file($dir . $path)) {
                // This path has a file, add it to the list
                $locale[] = $dir . $path;
            } else {
                //Fallback Locale
                $fallbackFile = str_replace($this->locale(), $this->getFallback(), $path);
                /*
                 | We will search for fallback locale if
                 | found we will load it into the list
                 */
                if (is_file($dir . $fallbackFile)) {
                    $locale[] = $dir . $fallbackFile;
                }
            }
        }

        return $locale;
    }

    /**
     * Returns the Translator array for a given language.
     *
     *     // Get all defined Spanish messages
     *     $messages = $trans->load('es');
     *
     * @param   string $locale   language to load
     * @return  array
     */
    public function load($locale)
    {
        if (isset($this->cache[$locale])) {
            return $this->cache[$locale];
        }

        // New Translator array
        $trans = [];
        // Split the language: language, region, locale, etc
        $parts = explode('-', $locale);

        do {
            // Create a path for this set of parts
            $path = implode(DIRECTORY_SEPARATOR, $parts);

            // Remove the last part
            if ($files = $this->findLanguageFile($path)) {
                $trans = $this->loadMessages($files, $trans);
            }

            array_pop($parts);

        } while ($parts);

        // Cache the Translator table locally
        return $this->cache[$locale] = $trans;
    }

    /**
     * We will load all messages into array
     *
     * @param $files
     * @param $trans
     * @return array
     */
    private function loadMessages($files, $trans)
    {
        $message = [];
        foreach ($files as $file) {
            // Merge the language strings into the sub message array
            if (is_readable($file)) {
                $message = array_merge($message, include $file);
            }
        }
        // Append to the sub message array, preventing overloading files
        $trans += $message;

        return $trans;
    }
}