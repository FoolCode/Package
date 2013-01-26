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
	 * Sets the directory of the package
	 *
	 * @param  string  $dir The path to the package
	 */
	public function __construct($dir)
	{
		$dir = rtrim($dir,'/').'/';
		if (! file_exists($dir.'composer.json'))
		{die($dir);
			throw new \DomainException('Directory not found.');
		}

		$this->dir = $dir;
	}

	/**
	 * Sets a loader to use the relative
	 *
	 * @param   \Foolz\Package\Loader  $loader
	 * @return  \Foolz\Package\Package
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
	 * Sets the dir_name used in the loader to get this package
	 *
	 * @param  string  $dir_name
	 */
	public function setDirName($dir_name)
	{
		$this->dir_name = $dir_name;
	}

	/**
	 * Returns the dir_name used in the loader to get this package
	 *
	 * @return  string
	 */
	public function getDirName()
	{
		return $this->dir_name;
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
		$psr = $this->getConfig('autoload.psr-0', false);

		if (! $psr)
		{
			return;
		}

		foreach ($psr as $key => $item)
		{
			$loader = new \Composer\Autoload\ClassLoader();
			$loader->add($key, $this->getDir().$item);
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
	 */
	public function getJsonConfig($section = null, $fallback = null)
	{
		if ($this->json_config === null)
		{
			$file = $this->getDir().'composer.json';

			// should never happen as we check for composer.json on instantiation
			if (! file_exists($file))
			{
				// @codeCoverageIgnoreStart
				throw new \DomainException;
				// @codeCoverageIgnoreEnd
			}

			$this->json_config = json_decode(file_get_contents($file), true);

			if ($this->json_config === null)
			{
				throw new \DomainException;
			}
		}

		if ($section === null)
		{
			return $this->json_config;
		}

		// if there wasn't an actual fallback set
		if (func_num_args() !== 2)
		{
			return Util::dottedConfig($this->json_config, $section, new Void);
		}

		return Util::dottedConfig($this->json_config, $section, $fallback);
	}

	/**
	 * Converts the JSON to a PHP config to improve speed
	 *
	 * @return  \Foolz\Package\Package
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
		if ($this->config === null)
		{
			$php_file = $this->getDir().'composer.php';

			if (file_exists($php_file) === false)
			{
				$this->jsonToConfig();
			}

			$this->config = include $php_file;
		}

		if ($section === null)
		{
			return $this->config;
		}

		// if there wasn't an actual fallback set
		if (func_num_args() !== 2)
		{
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
		if (file_exists($this->getDir().'composer.php'))
		{
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
}