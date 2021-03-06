<?php

namespace Clevis\Skeleton\Orm;

use Nette;
use Nette\Reflection;
use Orm;
use Orm\IRepository;
use Orm\Repository;
use Orm\RepositoryNotFoundException;


/**
 * Collection of IRepository.
 *
 * Cares about repository initialization.
 * It is the entry point into model from other parts of application.
 * Stores container of services which other objects may need.
 */
class RepositoryContainer extends Orm\RepositoryContainer
{

	/** @var array */
	private $aliases = [];

	/** @var array */
	private $repoClasses = [];

	/**
	 * Class constuctor – automatically registers repository aliases
	 *
	 * @param Orm\IServiceContainerFactory|Orm\IServiceContainer|NULL
	 */
	public function __construct($containerFactory = NULL)
	{
		parent::__construct($containerFactory);

		$this->registerAnnotations();
	}

	public function getAliases()
	{
		return $this->aliases;
	}

	public function register($alias, $repositoryClass)
	{
		$this->repoClasses[] = $repositoryClass;
		parent::register($alias, $repositoryClass);
	}

	public function getRepositoryClasses()
	{
		return $this->repoClasses;
	}

	/**
	 * Registers repositories from annotations
	 */
	private function registerAnnotations()
	{
		$ref = Nette\Reflection\ClassType::from($this);
		$annotations = $ref->getAnnotations();
		if (isset($annotations['property-read']))
		{
			$c = get_called_class();
			$namespace = substr($c, 0, strrpos($c, '\\'));
			foreach ($annotations['property-read'] as $value)
			{
				if (preg_match('#^([\\\\\\w]+Repository)\\s+\\$(\\w+)$#', $value, $m))
				{
					$class = '\\' . Reflection\AnnotationsParser::expandClassName($m[1], $ref);
					$this->register($m[2], $class);
					$this->aliases[$m[2]] = $class;
				}
			}
		}
	}

	/**
	 * Vrací instanci repository.
	 * Přednostně instancuje třídy vyjmenované v aliasech, přičemž bere v potaz dědičnost.
	 *
	 * @param string - repositoryClassName|alias
	 * @return Repository|IRepository
	 * @throws RepositoryNotFoundException
	 */
	public function getRepository($name)
	{
		$name = (string) $name;
		if (isset($this->aliases[$name]))
		{
			$repositoryClass = $this->aliases[$name];
			return parent::getRepository($repositoryClass);
		}
		else
		{
			$repositoryClass = NULL;
			foreach ($this->aliases as $alias => $class)
			{
				if (is_subclass_of($class, $name))
				{
					$repositoryClass = $class;
					break;
				}
			}
			$repository = parent::getRepository($repositoryClass ?: $name);
			$this->aliases[$name] = get_class($repository);
		}

		return $repository;
	}

	/**
	 * Black magic. Work-arround pro nefunkční RepositoryContainer::clean()
	 * Vymaže změny ve všech repozitářích (zapomene nové, změněné a načtené entity)
	 */
	public function purge()
	{
		$ref = new Reflection\ClassType('Orm\\RepositoryContainer');
		$ref = $ref->getProperty('repositories');
		$ref->setAccessible(TRUE);

		$repositories = $ref->getValue($this);

		foreach ($repositories as $repository)
		{
			$this->purgeRepository($repository);
		}
	}

	/**
	 * Black magic. Work-arround pro nefunkční Repository::clean()
	 * Vymaže změny v repozitáři (zapomene nové, změněné a načtené entity)
	 *
	 * @param Orm\Repository
	 */
	public function purgeRepository(Orm\Repository $repository)
	{
		$ref = new Reflection\ClassType('Orm\\IdentityMap');
		$ref = $ref->getProperty('entities');
		$ref->setAccessible(TRUE);

		$map = $repository->getIdentityMap();
		$ref->setValue($map, array());

		foreach ($map->getAllNew() as $entity)
		{
			$map->detach($entity);
		}
	}

}
