<?php

use Foolz\Package\Loader;

class LoaderTest extends PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$new = new Loader();
	}

	public function testForge()
	{
		$new = Loader::forge('default');
		$new2 = Loader::forge('default2');

		$this->assertInstanceOf('\Foolz\Package\Loader', $new);
		$this->assertFalse($new === $new2);
	}

	public function testDestroy()
	{
		$new = Loader::forge('default');
		Loader::destroy('default');
		$new2 = Loader::forge('default');
		$this->assertFalse($new === $new2);
	}

	/**
	 * @expectedException \DomainException
	 */
	public function testAddDirThrows()
	{
		$new = Loader::forge('default');
		$new->addDir('test', __DIR__.'/../../tests/moewufck/');
	}

	public function testAddDirWithoutName()
	{
		$new = Loader::forge('default');
		$dir = __DIR__.'/../../tests/mock/';
		$new->addDir($dir);
		$array = $new->getAll();
		$this->assertArrayHasKey($dir, $array);
		$this->assertArrayHasKey('foolz/fake', $array[$dir]);
	}

	public function testAddDir()
	{
		$new = Loader::forge('default');
		$new->addDir('test', __DIR__.'/../../tests/mock/');
		$array = $new->getAll();
		$this->assertArrayHasKey('test', $array);
		$this->assertArrayHasKey('foolz/fake', $array['test']);
	}

	public function testRemoveDir()
	{
		$new = Loader::forge('default');
		$new->addDir('test', __DIR__.'/../../tests/mock/');
		$new->getAll();
		$new->removeDir('test');
		$array = $new->getAll();
		$this->assertArrayNotHasKey('test', $array);
	}

	/**
	 * @expectedException \OutOfBoundsException
	 */
	public function testGetPackagesThrow()
	{
		$new = Loader::forge('default');
		$new->addDir('test', __DIR__.'/../../tests/mock/');
		$new->getAll('trest');
	}

	public function testGetPackagesKey()
	{
		$new = Loader::forge('default');
		$new->addDir('test', __DIR__.'/../../tests/mock/');
		$array = $new->getAll('test');
		$this->assertArrayHasKey('foolz/fake', $array);
	}

	public function testGetPackage()
	{
		$new = Loader::forge('default');
		$new->addDir('test', __DIR__.'/../../tests/mock/');
		$package = $new->get('test', 'foolz/fake');
		$this->assertInstanceOf('Foolz\Package\Package', $package);
	}

	/**
	 * @expectedException \OutOfBoundsException
	 */
	public function testGetPackageThrows()
	{
		$new = Loader::forge('default');
		$new->addDir('test', __DIR__.'/../../tests/mock/');
		$package = $new->get('test', 'foolz/faker');
	}
}