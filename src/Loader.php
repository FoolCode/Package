<?php

namespace Foolz\Package;

/**
 * Automates loading of plugins
 *
 * @author   Foolz <support@foolz.us>
 * @package  Foolz\Package
 * @license  http://www.apache.org/licenses/LICENSE-2.0.html Apache License 2.0
 */
class Loader
{
    /**
     * The type of package in use. Can be in example 'theme' or 'plugin'
     * Override this to change type of package
     *
     * @var  string
     */
    protected $type_name = 'package';

    /**
     * The class into which the resulting objects are created.
     * Override this, in example Foolz\Plugin\Plugin or Foolz\Theme\Theme
     *
     * @var  string
     */
    protected $type_class = 'Foolz\Package\Package';

    /**
     * The instances of the Loader class
     *
     * @var  \Foolz\Package\Loader[]
     */
    protected static $instances = [];

    /**
     * The dirs in which to look for packages
     *
     * @var  null|array
     */
    protected $dirs = null;

    /**
     * The packages found
     *
     * @var  null|array  as first key the dir name, as second key the slug
     */
    protected $packages = null;

    /**
     * Tells if packages should be reloaded
     *
     * @var  boolean
     */
    protected $reload = true;

    /**
     * The Composer autoloader
     *
     * @var  \Composer\Autoload\ClassLoader
     */
    protected $composer_loader = null;

    /**
     * Creates or returns a named instance of Loader
     *
     * @param   string  $instance  The name of the instance to use or create
     *
     * @return  \Foolz\Package\Loader
     */
    public static function forge($instance = 'default')
    {
        if (!isset(static::$instances[$instance])) {
            return static::$instances[$instance] = new static();
        }

        return static::$instances[$instance];
    }

    /**
     * Destroys a named instance and unregisters its autoloader
     *
     * @param  string  $instance  The name of the instance to use or create
     */
    public static function destroy($instance = 'default')
    {
        unset(static::$instances[$instance]);
    }

    /**
     * Adds a directory to the array of directories to search packages in
     *
     * @param   string       $dir_name  If $dir is not set this sets both the name and the dir equal
     * @param   null|string  $dir       The dir where to look for packages
     *
     * @return  \Foolz\Package\Loader  The current object
     * @throws  \DomainException      If the directory is not found
     */
    public function addDir($dir = null)
    {
        if (!is_dir($dir)) {
            throw new \DomainException('Directory not found.');
        }

        $this->dirs[] = ['path' => rtrim($dir, '/'), 'loaded' => false];

        return $this;
    }

    /**
     * Looks for packages in the specified directories and creates the objects
     */
    public function find()
    {
        if ($this->packages === null) {
            $this->packages = array();
        }

        foreach ($this->dirs as $pack => $dir) {
            if ($dir['loaded'] === false) {
                $vendors = $this->findDirs($dir['path']);

                foreach ($vendors as $vendor_name => $vendor_path) {
                    $packages = $this->findDirs($vendor_path);

                    foreach ($packages as $package_name => $package_path) {
                        if (!isset($this->packages[$vendor_name.'/'.$package_name])) {
                            $package = new $this->type_class($package_path);
                            $package->setLoader($this);

                            $this->packages[$vendor_name.'/'.$package_name] = $package;
                        }
                    }
                }

                $this->dirs[$pack]['loaded'] = true;
            }
        }
    }

    /**
     * Internal function to find all directories at the path
     *
     * @param   string  $path  The path to look into
     *
     * @return  array   The paths with as they the last part of the path
     */
    protected function findDirs($path)
    {
        $result = array();
        $fp = opendir($path);

        while (false !== ($file = readdir($fp))) {
            // Remove '.', '..'
            if (in_array($file, array('.', '..'))) {
                continue;
            }

            if (is_dir($path.'/'.$file)) {
                $result[$file] = $path.'/'.$file;
            }
        }

        closedir($fp);

        return $result;
    }

    /**
     * Gets all the packages or the packages from the directory
     *
     * @return  \Foolz\Package\Package[]  All the packages or the packages in the directory
     * @throws  \OutOfBoundsException   If there isn't such a $dir_name set
     */
    public function getAll()
    {
        $this->find();

        return $this->packages;
    }

    /**
     * Gets a single package object
     *
     * @param   string  $slug               The slug of the package
     *
     * @return  \Foolz\Package\Package
     * @throws  \OutOfBoundsException  if the package doesn't exist
     */
    public function get($slug)
    {
        $packages = $this->getAll();

        if (!isset($packages[$slug])) {
            throw new \OutOfBoundsException('There is no such a package.');
        }

        return $packages[$slug];
    }

    /**
     * Set the public directory where files can be copied into
     *
     * @param  $public_dir  The path
     *
     * @return \Foolz\Theme\Loader
     */
    public function setPublicDir($public_dir)
    {
        $this->public_dir = rtrim($public_dir, '/').'/';

        return $this;
    }

    /**
     * Returns the public directory where files are copied into
     *
     * @return  string  The path
     * @throws  \BadMethodCallException  If the public dir wasn't set
     */
    public function getPublicDir()
    {
        if ($this->public_dir === null) {
            throw new \BadMethodCallException('The public dir was not set.');
        }

        return $this->public_dir;
    }

    /**
     * Set the base URL that points to the public directory
     *
     * @param  $base_url  The URL
     *
     * @return  \Foolz\Theme\Loader
     */
    public function setBaseUrl($base_url)
    {
        $this->base_url = rtrim($base_url, '/').'/';

        return $this;
    }

    /**
     * Returns the base URL that points to the public directory
     *
     * @return  string  The URL
     * @throws  \BadMethodCallException  If the base url wasn't set
     */
    public function getBaseUrl()
    {
        if ($this->base_url === null) {
            throw new \BadMethodCallException('The base url was not set.');
        }

        return $this->base_url;
    }

    /**
     * @return \Foolz\Theme\Loader
     */
    public function reload()
    {
        foreach ($this->dirs as $pack => $dir) {
            $this->dirs[$pack]['loaded'] = false;
        }

        $this->packages = array();

        return $this;
    }
}
