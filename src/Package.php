<?php

namespace Foolz\Package;

/**
 * Holds data on a package package
 *
 * @author Foolz <support@foolz.us>
 * @package Foolz\Package
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache License 2.0
 */
class Package
{
    /**
     * The Loader object that created this object
     *
     * @var \Foolz\Package\Loader
     */
    protected $loader;

    /**
     * The name given to the dir to recall it from the loader
     *
     * @var type
     */
    protected $dir_name;

    /**
     * The path to this package
     *
     * @var string
     */
    protected $dir;

    /**
     * Loaded JSON config
     *
     * @var null|array
     */
    protected $json_config = null;

    /**
     * Loaded PHP config
     *
     * @var null|array
     */
    protected $config = null;

    /**
     * Remember if the plugin has been bootstrapped already
     *
     * @var boolean
     */
    protected $bootstrapped = false;

    /**
     * Stores the asset manager for the package
     *
     * @var AssetManager|null
     */
    protected $asset_manager = null;

    /**
     * Sets the directory of the package
     *
     * @param  string  $dir The path to the package
     *
     * @throws  \DomainException  When the path is not found
     */
    public function __construct($dir)
    {
        $dir = rtrim($dir, '/').'/';

        if (!file_exists($dir.'composer.json')) {
            throw new \DomainException('Directory not found.');
        }

        $this->dir = $dir;
    }

    /**
     * Sets a loader to use the relative
     *
     * @param   \Foolz\Package\Loader  $loader
     *
     * @return $this
     */
    public function setLoader(\Foolz\Package\Loader $loader)
    {
        $this->loader = $loader;

        return $this;
    }

    /**
     * Gets the loader that created this object
     *
     * @return  \Foolz\Package\Loader
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Gets the path to the package
     *
     * @return  string
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * Plugs the package in the Composer autoloader
     */
    public function enableAutoloader()
    {
        $psr0 = $this->getConfig('autoload.psr-0', []);
        $psr4 = $this->getConfig('autoload.psr-4', []);

        if (!empty($psr0) || !empty($psr4)) {
            $loader = new \Composer\Autoload\ClassLoader();

            // load psr-0
            foreach ($psr0 as $namespace => $path) {
                $loader->add($namespace, $this->getDir().$path);
            }

            // load psr-4
            foreach ($psr4 as $namespace => $path) {
                $loader->addPsr4($namespace, $this->getDir().$path);
            }

            $loader->register();
        }
    }

    /**
     * Gets the content of the composer.json
     *
     * @param   string  $section  keys of the array separated by dots
     * @param   mixed   $fallback
     * @return  mixed
     * @throws  \DomainException  if there is no such config item and there was no fallback set
     *
     * @deprecated  23/03/2013  Trying to use JSON straight away
     */
    public function getJsonConfig($section = null, $fallback = null)
    {
        if ($this->json_config === null) {
            $file = $this->getDir().'composer.json';

            // should never happen as we check for composer.json on instantiation
            if (!file_exists($file)) {
                // @codeCoverageIgnoreStart
                throw new \DomainException;
                // @codeCoverageIgnoreEnd
            }

            $this->json_config = json_decode(file_get_contents($file), true);

            if ($this->json_config === null) {
                throw new \DomainException;
            }
        }

        if ($section === null) {
            return $this->json_config;
        }

        // if there wasn't an actual fallback set
        if (func_num_args() !== 2) {
            return Util::dottedConfig($this->json_config, $section, new Void);
        }

        return Util::dottedConfig($this->json_config, $section, $fallback);
    }

    /**
     * Converts the JSON to a PHP config to improve speed
     *
     * @return  \Foolz\Package\Package
     *
     * @deprecated  23/03/2013  Trying to use JSON straight away
     */
    public function jsonToConfig()
    {
        $config = $this->getJsonConfig();

        Util::saveArrayToFile($this->getDir().'composer.php', $config);

        return $this;
    }

    /**
     * Gets the content of the config
     *
     * @param   string  $section  keys of the array separated by dots
     * @param   mixed   $fallback
     * @return  mixed
     * @throws  \DomainException  if there is no such config item and there was no fallback set
     */
    public function getConfig($section = null, $fallback = null)
    {

        if ($this->config === null) {
            $this->config = json_decode(file_get_contents($this->getDir().'composer.json'), true);
        }

        if ($section === null) {
            return $this->config;
        }

        // if there wasn't an actual fallback set
        if (func_num_args() !== 2) {
            return Util::dottedConfig($this->config, $section, new Void);
        }

        return Util::dottedConfig($this->config, $section, $fallback);
    }

    /**
     * Destroys the composer.php to recreate it from the composer.json
     *
     * @return  \Foolz\Package\Package
     */
    public function refreshConfig()
    {
        if (file_exists($this->getDir().'composer.php')) {
            unlink($this->getDir().'composer.php');
        }

        $this->clearJsonConfig();
        $this->clearConfig();
        return $this;
    }

    /**
     * Clears the json_config variable to reload from JSON
     *
     * @return  \Foolz\Package\Package
     *
     * @deprecated  23/03/2013  Trying to use JSON straight away
     */
    public function clearJsonConfig()
    {
        $this->json_config = null;

        return $this;
    }

    /**
     * Clears the config variable to reload from composer.php
     *
     * @return  \Foolz\Package\Package
     */
    public function clearConfig()
    {
        $this->config = null;

        return $this;
    }

    /**
     * Runs the bootstrap file
     *
     * @return  \Foolz\Package\Package
     */
    public function bootstrap()
    {
        include $this->getDir().'bootstrap.php';
        $this->bootstrapped = true;

        return $this;
    }

    /**
     * Tells if the bootstrap file has been run at least once
     *
     * @return  boolean  True if the bootstrap file has been run, false otherwise
     */
    public function isBootstrapped()
    {
        return $this->bootstrapped;
    }

    /**
     * Returns an AssetManager object to deal with the assets
     *
     * @return  \Foolz\Package\AssetManager  A new instance of the AssetManager
     */
    public function getAssetManager()
    {
        if ($this->asset_manager !== null) {
            return $this->asset_manager;
        }

        return $this->asset_manager = new AssetManager($this);
    }

    /**
     * Checks for the existence of a theme to extend and returns the theme
     *
     * @return  \Foolz\Package\Package  The base theme we're extending with the current
     * @throws  \OutOfBoundsException   If the theme is not found or no extended theme has been specified
     */
    public function getExtended()
    {
        $extended = $this->getConfig('extra.extends', null);

        if ($extended === null) {
            throw new \OutOfBoundsException('No package to extend.');
        }

        try {
            return $this->getLoader()->get($extended);
        } catch (\OutOfBoundsException $e) {
            throw new \OutOfBoundsException('No such package available for extension.');
        }
    }
}
