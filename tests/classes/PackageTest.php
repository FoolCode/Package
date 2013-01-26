<?php

use Foolz\Package\Package;
use Foolz\Package\Loader;

class PackageTest extends PHPUnit_Framework_TestCase
{
	public function unlinkConfig()
	{
		if (file_exists(__DIR__.'/../../tests/mock/foolz/fake/composer.php'))
		{
			unlink(__DIR__.'/../../tests/mock/foolz/fake/composer.php');
		}
	}

	public function testConstruct()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');
		$this->assertInstanceOf('Foolz\Package\Package', $package);
	}

	/**
	 * @expectedException \DomainException
	 */
	public function testConstructThrows()
	{
		$package = new Package(__DIR__.'/../../tests/mock/blabla');
	}

	public function testGetSetLoader()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');
		$loader = new Loader();
		$package->setLoader($loader);
		$this->assertSame($loader, $package->getLoader());
	}

	public function testGetDir()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake');
		$this->assertFalse(__DIR__.'/../../tests/mock/foolz/fake' === $package->getDir());

		// it always adds a trailing slash
		$this->assertSame(__DIR__.'/../../tests/mock/foolz/fake/', $package->getDir());
	}

	public function testGetJsonConfig()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');
		$this->assertArrayHasKey('name', $package->getJsonConfig());
	}

	public function testGetJsonConfigKey()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');
		$this->assertSame('Fake', $package->getJsonConfig('extra.name'));
	}

	public function testGetJsonConfigKeyFallback()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');
		$this->assertSame('Fake', $package->getJsonConfig('extra.doesntexist', 'Fake'));
	}

	/**
	 * @expectedException \DomainException
	 */
	public function testGetJsonConfigKeyThrows()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');
		$this->assertSame('Fake', $package->getJsonConfig('extra.doesntexist'));
	}

	/**
	 * @expectedException \DomainException
	 */
	public function testGetJsonConfigBrokenJsonThrows()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/broken_json/');
		$package->getJsonConfig();
	}

	public function testJsonToConfig()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');
		$package->jsonToConfig();
		$this->assertSame($package->getJsonConfig(), $package->getConfig());
		$this->unlinkConfig();
	}

	public function testGetConfig()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');
		$this->assertArrayHasKey('name', $package->getConfig());
		$this->unlinkConfig();
	}

	public function testGetConfigKey()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');
		$this->assertSame('Fake', $package->getConfig('extra.name'));
		$this->unlinkConfig();
	}

	public function testGetConfigKeyFallback()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');
		$this->assertSame('Fake', $package->getConfig('extra.doesntexist', 'Fake'));
		$this->unlinkConfig();
	}

	/**
	 * @expectedException \DomainException
	 */
	public function testGetConfigKeyFallbackThrows()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');
		$package->getConfig('extra.doesntexist');
		$this->unlinkConfig();
	}

	public function testRefreshConfig()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');
		$package->getConfig();

		$package->refreshConfig();
		$this->assertFalse(file_exists(__DIR__.'/../../tests/mock/foolz/fake/composer.php'));
		$this->unlinkConfig();
	}

	public function testComposerClassLoader()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');

		$package->enableAutoloader();
		$this->assertSame('I am fake.', \Foolz\Fake\Fake::fake());
		$this->unlinkConfig();
	}

	public function testIsBootstrapped()
	{
		$package = new Package(__DIR__.'/../../tests/mock/foolz/fake/');
		$package->enableAutoloader();
		$this->assertTrue($this->bootstrapped);
		$this->unlinkConfig();
	}
}