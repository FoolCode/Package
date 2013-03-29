<?php

namespace Foolz\Package;

class AssetManager
{
	/**
	 * The package creating this object
	 *
	 * @var  \Foolz\Package\Package|null
	 */
	protected $package = null;

	/**
	 * The directory where the files should be put so they are reachable via an URL
	 *
	 * @var  string
	 */
	protected $public_dir = "";

	/**
	 * The base URL where the package files can be found at
	 *
	 * @var  string
	 */
	protected $base_url = "";

	/**
	 * Create a new instance of the asset manager
	 *
	 * @param  \Foolz\Package\Package  $package  The reference to the package creating this asset manager
	 *
	 * @return  \Foolz\Package\AssetManager
	 */
	public function __construct(\Foolz\Package\Package $package)
	{
		$this->package = $package;
		$this->public_dir = $this->getPackage()->getLoader()->getPublicDir();
		$this->base_url = $this->getPackage()->getLoader()->getBaseUrl();

		// load the assets
		if ( ! file_exists($this->getPublicDir()))
		{
			$this->clearAssets();
			$this->loadAssets();
		}
	}

	/**
	 * Returns the Package object that created this instance of AssetManager
	 *
	 * @return  \Foolz\Package\Package|null  The Package object that created this instance of AssetManager
	 */
	public function getPackage()
	{
		return $this->package;
	}

	/**
	 * Returns the path to the directory where the public files get loaded
	 *
	 * @return  string  The path
	 */
	protected function getPublicDir()
	{
		return $this->public_dir.$this->getPackage()->getConfig('name')
			.'/assets-'.$this->getPackage()->getConfig('version').'/';
	}

	/**
	 * Returns an URL to the asset being requested
	 *
	 * @param  $path  $path  The relative path to the asset to link to
	 *
	 * @return  string  The full URL to the asset
	 */
	public function getAssetLink($path)
	{
		$candidate = $this->base_url.$this->getPackage()->getConfig('name')
			.'/assets-'.$this->getPackage()->getConfig('version').'/'.$path;

		if (file_exists($this->getPublicDir().$path))
		{
			return $candidate;
		}

		return $this->getPackage()->getExtended()->getAssetManager()->getAssetLink($path);
	}

	/**
	 * Loads all the asset files from the package folder
	 */
	protected function loadAssets()
	{
		if ( ! file_exists($this->getPublicDir()))
		{
			mkdir($this->getPublicDir(), 0777, true);
		}

		// damned copy doesn't work with directories
		//copy($this->getPackage()->getDir().'assets', $this->getPublicDir());
		system('cp -R '.$this->getPackage()->getDir().'assets/*'.' '.$this->getPublicDir());
	}

	/**
	 * Clears all the files in the public package directory
	 *
	 * @return  \Foolz\Package\AssetManager  The current object
	 */
	public function clearAssets()
	{
		// get it just right out of the assets folder
		if (file_exists($this->public_dir.$this->getPackage()->getConfig('name')))
		{
			static::flushDir($this->public_dir.$this->getPackage()->getConfig('name'));
		}

		return $this;
	}

	/**
	 * Empties a directory
	 *
	 * @param  string  $path  The directory to empty
	 */
	protected static function flushDir($path)
	{
		$fp = opendir($path);

		while (false !== ($file = readdir($fp)))
		{
			// Remove '.', '..'
			if (in_array($file, array('.', '..')))
			{
				continue;
			}

			$filepath = $path.'/'.$file;

			if (is_dir($filepath))
			{
				static::flushDir($filepath);

				// removing dir here won't remove the root dir, just as we want it
				rmdir($filepath);
				continue;
			}
			elseif (is_file($filepath))
			{
				unlink($filepath);
			}
		}

		closedir($fp);
	}
}