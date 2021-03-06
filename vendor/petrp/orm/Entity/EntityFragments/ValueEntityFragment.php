<?php
/**
 * Orm
 * @author Petr Procházka (petr@petrp.cz)
 * @license "New" BSD License
 */

namespace Orm;

use Exception;
use ReflectionMethod;
use Tracy\Debugger;


/**
 * Performs all reading, checking, filling, setting and validation of data.
 * @see Entity
 * @author Petr Procházka
 * @package Orm
 * @subpackage Entity\EntityFragments
 */
abstract class ValueEntityFragment extends AttachableEntityFragment
{

	/** @var array of mixed Hodnoty parametru */
	private $values = [];

	/** @var array of bool Jsou parametry validni? */
	private $valid = [];

	/** @var array internal format MetaData */
	private $rules;

	/** @var array Byla zmenena nejaka hodnota na teto entite od posledniho ulozeni? */
	private $changed = [];

	/** @var array Za behu informace ktere metody se volaji, aby bylo mozne magicke pretezovani */
	private $overwriteMethodTemp = [];

	/**
	 * Existuje tento parametr?
	 * Mozno i zjisti jestli je pro cteni/zapis.
	 * @param string
	 * @param int|NULL MetaData::READWRITE MetaData::READ MetaData::WRITE
	 * @return bool
	 */
	final public function hasParam($name, $mode = NULL)
	{
		if ($mode === NULL) return isset($this->rules[$name]);
		if (!isset($this->rules[$name])) return false;

		$rule = $this->rules[$name];
		if ($mode === MetaData::READWRITE) return isset($rule['get']) AND isset($rule['set']);
		else if ($mode === MetaData::READ) return isset($rule['get']);
		else if ($mode === MetaData::WRITE) return isset($rule['set']);
		throw new InvalidArgumentException(['Orm\Entity', 'hasParam() $mode', 'Orm\MetaData::READWRITE, READ or WRITE', $mode]);
	}

	/**
	 * Pouziva se pouze ve vlastnich geterech.
	 * Vrati hodnotu parametru, ale nepouzije getter
	 * @param string
	 * @param bool
	 * @return mixed
	 */
	final protected function getValue($name, $need = true)
	{
		if (!isset($this->rules[$name]))
		{
			throw new PropertyAccessException("Cannot read an undeclared property ".get_class($this)."::\$$name.");
		}
		unset($this->overwriteMethodTemp['get'][$name]);

		$rule = $this->rules[$name];

		if (!isset($rule['get']))
		{
			throw new PropertyAccessException("Cannot read to a write-only property ".get_class($this)."::\$$name.");
		}

		$value = IEntity::DEFAULT_VALUE;
		$valid = false;
		if (isset($this->values[$name]) OR array_key_exists($name, $this->values))
		{
			$valid = isset($this->valid[$name]) ? $this->valid[$name] : false;
			$value = $this->values[$name];
		}
		else
		{
			$this->values[$name] = NULL; // zabranuje opakovanemu volani default a lazyload, kdyz setter nic nedela
			if ($this->getRepository(false)) // lazy load
			{
				if ($lazyLoadParams = $this->getRepository()->lazyLoad($this, $name))
				{
					foreach ($lazyLoadParams as $n => $v)
					{
						if ($name == $n OR !array_key_exists($n, $this->values))
						{
							$this->values[$n] = $v;
							$this->valid[$n] = false;
						}
					}
					if (array_key_exists($name, $this->values))
					{
						$value = $this->values[$name];
					}
				}
			}
		}

		if (!$valid)
		{
			$tmpChanged = $this->changed;
			try {
				if (isset($rule['set']))
				{
					$this->__set($name, $value);
					$this->valid[$name] = true; // zabranuje opakovanemu volani setteru, kdyz ten nic nedela
				}
				else
				{
					$this->setValueHelper($name, $value, $rule);
				}
			} catch (Exception $e) {
				$this->changed = $tmpChanged;
				if (!($e instanceof NotValidException) OR $need)
				{
					throw $e;
				}
				return NULL;
			}
			$this->changed = $tmpChanged;
			$value = isset($this->values[$name]) ? $this->values[$name] : NULL; // todo kdyz neni nastaveno muze to znamenat neco spatne, vyhodit chybu?
		}

		return $value;
	}

	/**
	 * Pouziva se pouze ve vlastnich seterech.
	 * Nastavi hodnotu parametru, ale nepouzije setter
	 * @param string
	 * @param mixed
	 * @return Entity $this
	 */
	final protected function setValue($name, $value)
	{
		if (!isset($this->rules[$name]))
		{
			throw new PropertyAccessException("Cannot write to an undeclared property ".get_class($this)."::\$$name.");
		}
		unset($this->overwriteMethodTemp['set'][$name]);

		$rule = $this->rules[$name];

		if (!isset($rule['set']))
		{
			throw new PropertyAccessException("Cannot write to a read-only property ".get_class($this)."::\$$name.");
		}

		$this->setValueHelper($name, $value, $rule);

		return $this;
	}

	/**
	 * Nastavi read-only property
	 * @param string
	 * @param mixed
	 * @return Entity $this
	 */
	final protected function setReadOnlyValue($name, $value)
	{
		if (!isset($this->rules[$name]))
		{
			throw new PropertyAccessException("Cannot write to an undeclared property ".get_class($this)."::\$$name.");
		}

		$rule = $this->rules[$name];

		if (isset($rule['set']))
		{
			throw new PropertyAccessException("Property ".get_class($this)."::\$$name is not read-only.");
		}

		$this->setValueHelper($name, $value, $rule);

		return $this;
	}

	/**
	 * Byla zmenena nejaka hodnota na teto entite od posledniho ulozeni?
	 * @param string|NULL
	 * @return bool
	 * @see self::$changed
	 * @see self::markAsChanged()
	 */
	final public function isChanged($name = NULL)
	{
		if (func_num_args() > 0 AND func_get_arg(0) === true)
		{
			throw new DeprecatedException(['Orm\Entity', 'isChanged(TRUE)', 'Orm\Entity', 'markAsChanged()']);
		}
		if ($name === NULL)
		{
			return (bool) $this->changed;
		}
		if (!isset($this->rules[$name]))
		{
			throw new PropertyAccessException("Cannot check an undeclared property ".get_class($this)."::\$$name.");
		}
		return isset($this->changed[NULL]) OR isset($this->changed[$name]);
	}

	/**
	 * Nastavit, ze tato entita byla zmenena.
	 * @param string|NULL
	 * @return IEntity $this
	 * @see self::$changed
	 * @see self::isChanged()
	 */
	final public function markAsChanged($name = NULL)
	{
		if ($name !== NULL AND !isset($this->rules[$name]))
		{
			throw new PropertyAccessException("Cannot mark as changed an undeclared property ".get_class($this)."::\$$name.");
		}
		$this->changed[$name] = true;
		return $this;
	}

	/** Vytvorena nova entita */
	protected function onCreate()
	{
		parent::onCreate();
		$this->changed[NULL] = true;
		$this->rules = MetaData::getEntityRules(get_class($this));
	}

	/**
	 * Pripojeno na model (nemusi se volat vzdy, ale jen kdyz jeste neni pripojeno na repository)
	 * @param IRepositoryContainer
	 */
	final protected function onAttachModel(IRepositoryContainer $model)
	{
		parent::onAttachModel($model);
		$this->rules = MetaData::getEntityRules(get_class($this), $model);
	}

	/**
	 * Pripojeno na repository
	 * @param IRepository
	 */
	protected function onAttach(IRepository $repository)
	{
		parent::onAttach($repository);
		$this->rules = MetaData::getEntityRules(get_class($this), $repository->getModel());
	}

	/**
	 * Vytazena z mapperu
	 * @param IRepository
	 * @param array
	 */
	protected function onLoad(IRepository $repository, array $data)
	{
		parent::onLoad($repository, $data);
		$this->rules = MetaData::getEntityRules(get_class($this), $repository->getModel());
		$this->values = $data;
		$this->valid = [];
		$this->getValue('id'); // throw error if any
	}

	/**
	 * Behem persistovani, vsechny subentity nemusi byt jeste persistovany
	 * @param IRepository
	 * @param scalar
	 */
	final protected function onPersist(IRepository $repository, $id)
	{
		parent::onPersist($repository, $id);
		$this->values['id'] = $id;
		$this->valid['id'] = false;
		$this->getValue('id'); // throw error if any
		$this->changed = [];
	}

	/**
	 * Před vymazanim
	 * @param IRepository
	 */
	protected function onBeforeRemove(IRepository $repository)
	{
		foreach ($this->rules as $property => $rule)
		{
			if ($rule['relationship'] === MetaData::ManyToMany OR $rule['relationship'] === MetaData::OneToMany)
			{
				$param = $rule['relationshipParam']->getChildParam();
				foreach ($this->{$property} as $entity)
				{
					if (isset($entity->{$param}))
					{
						$entity->{$param};
					}
				}
			}
			else if ($rule['relationship'] === MetaData::ManyToOne OR $rule['relationship'] === MetaData::OneToOne)
			{
				if (isset($this->{$property}))
				{
					$this->{$property};
				}
			}
		}
		parent::onBeforeRemove($repository);
	}

	/**
	 * Po vymazani
	 * @param IRepository
	 */
	protected function onAfterRemove(IRepository $repository)
	{
		foreach ($this->rules as $property => $rule)
		{
			if ($rule['relationship'] === MetaData::ManyToMany OR $rule['relationship'] === MetaData::OneToMany)
			{
				$many = $this->{$property};
				foreach ($many as $entity)
				{
					$many->remove($entity);
				}
			}
			else if ($rule['relationship'] === MetaData::ManyToOne OR $rule['relationship'] === MetaData::OneToOne)
			{
				$meta = $rule['relationshipParam'];
				if ($childParam = $meta->getChildParam())
				{
					if (isset($this->{$property}))
					{
						$entity = $this->{$property};
						if ($rule['relationship'] === MetaData::ManyToOne)
						{
							unset($this->values[$property]);
							unset($this->valid[$property]);
							$entity->{$childParam}->remove($this, 'handled by ManyToOne remove');
						}
						else if ($rule['relationship'] === MetaData::OneToOne)
						{
							unset($this->values[$property]);
							unset($this->valid[$property]);
							if ($entity instanceof self)
							{
								unset($entity->values[$childParam]);
								unset($entity->valid[$childParam]);
							}
							else
							{ // @codeCoverageIgnoreStart
								$entity->{$childParam} = NULL;
							} // @codeCoverageIgnoreEnd
							$entity->markAsChanged($childParam);
						}
					}
				}
			}
		}
		$this->changed[NULL] = true;
		parent::onAfterRemove($repository);
		$this->values['id'] = NULL;
		$this->valid['id'] = false;
	}

	/** Pri klonovani vznika nova entita se stejnejma datama */
	public function __clone()
	{
		$this->values['id'] = NULL;
		$this->valid = [];
		$this->changed[NULL] = true;
		foreach ($this->values as $property => $value)
		{
			if ($value instanceof IRelationship)
			{
				unset($this->values[$property]);
				$this->{$property} = iterator_to_array($value);
			}
			else if ($value instanceof IEntityInjection)
			{
				$this->values[$property] = $value->getInjectedValue();
			}
			else if (!($value instanceof IEntity) AND is_object($value))
			{
				$this->values[$property] = clone $value;
			}
		}
		parent::__clone();
	}

	/**
	 * Pristup k parametru jako k property.
	 * @param string
	 * @return mixed
	 * @throws PropertyAccessException
	 */
	final public function & __get($name)
	{
		if (!isset($this->rules[$name]))
		{
			$tmp = parent::__get($name);
			return $tmp;
		}

		$rule = $this->rules[$name];

		if (!isset($rule['get']))
		{
			throw new PropertyAccessException("Cannot read to a write-only property ".get_class($this)."::\$$name.");
		}

		$value = NULL;
		if ($rule['get']['method'])
		{
			if (!isset($this->overwriteMethodTemp['get'][$name]))
			{
				$this->overwriteMethodTemp['get'][$name] = true;
				try {
					$value = $this->{$rule['get']['method']}(); // todo mohlo by zavolat private metodu, je potreba aby vse bylo final
				} catch (Exception $e) {
					unset($this->overwriteMethodTemp['get'][$name]);
					throw $e;
				}
				unset($this->overwriteMethodTemp['get'][$name]);
			}
			else
			{
				unset($this->overwriteMethodTemp['get'][$name]);
				$value = $this->getValue($name);
			}
		}
		else
		{
			$value = $this->getValue($name);
		}

		return $value;
	}

	/**
	 * Nastav parametr jako property.
	 * @param string
	 * @param mixed
	 * @return Entity $this
	 * @throws PropertyAccessException
	 * @throws NotValidException
	 */
	final public function __set($name, $value)
	{
		if (!isset($this->rules[$name]))
		{
			return parent::__set($name, $value);
		}

		$rule = $this->rules[$name];

		if (!isset($rule['set']))
		{
			throw new PropertyAccessException("Cannot write to a read-only property ".get_class($this)."::\$$name.");
		}
		if ($rule['set']['method'])
		{
			if ($value === IEntity::DEFAULT_VALUE)
			{
				$value = $this->getDefaultValueHelper($name, $rule);
			}
			if (!isset($this->overwriteMethodTemp['set'][$name]))
			{
				$this->overwriteMethodTemp['set'][$name] = true;
				try {
					$this->{$rule['set']['method']}($value); // todo mohlo by zavolat private metodu, je potreba aby vse bylo final
				} catch (Exception $e) {
					unset($this->overwriteMethodTemp['set'][$name]);
					throw $e;
				}
				unset($this->overwriteMethodTemp['set'][$name]);
			}
			else
			{
				unset($this->overwriteMethodTemp['set'][$name]);
				return $this->setValue($name, $value);
			}
		}
		else
		{
			$this->setValue($name, $value);
		}

		return $this;
	}

	/**
	 * Pristup/nastaveni parametru prese getter/setter `get<Param>` `set<Param>`
	 * @param string
	 * @param array
	 * @return mixed
	 * @throws PropertyAccessException
	 * @throws NotValidException
	 */
	final public function __call($name, $args)
	{
		$m = $var = NULL;
		if (strncmp($name, 'get', 3) === 0 OR (strncmp($name, 'set', 3) === 0 AND array_key_exists(0, $args)))
		{
			$m = substr($name, 0, 3);
			$var = substr($name, 3);
		}
		else if (strncmp($name, 'is', 2) === 0)
		{
			$m = 'is';
			$var = substr($name, 2);
		}

		if ($m !== NULL)
		{
			if ($var{0} != '_') $var{0} = $var{0} | "\x20"; // lcfirst
			if (isset($this->rules[$var]))
			{
				if ($m !== 'is' OR ($m === 'is' AND $this->rules[$var]['types'] === ['bool' => 'bool']))
				{
					if ($m === 'is') $m = 'get';
					$this->overwriteMethodTemp[$m][$var] = true;
					return $this->{'__' . $m}($var, $m === 'set' ? $args[0] : NULL);
				}
			}
		}

		return parent::__call($name, $args);
	}

	/**
	 * Existuje parametr?
	 * @param string
	 * @return bool
	 */
	final public function __isset($name)
	{
		if (isset($this->rules[$name]['get']))
		{
			if (isset($this->valid[$name]) AND $this->valid[$name])
			{
				return isset($this->values[$name]);
			}
			try {
				return $this->__get($name) !== NULL;
			} catch (Exception $e) {
				return false;
			}
		}

		return parent::__isset($name);
	}

	/**
	 * Vrati defaultni hodnotu parametru.
	 * @param string
	 * @param array
	 * @return mixed
	 */
	final private function getDefaultValueHelper($name, $rule)
	{
		$default = NULL;
		if (isset($rule['default']))
		{
			$default = $rule['default'];
		}
		else
		{
			$defaultMethod = "getDefault$name";
			$defaultMethod{10} = $defaultMethod{10} & "\xDF"; // ucfirst
			if (method_exists($this, $defaultMethod))
			{
				$r = new ReflectionMethod($this, $defaultMethod);
				// todo predelat aby se methody zjistovali jednou v MetaData
				if (!$r->isPrivate())
				{
					$default = $this->{$defaultMethod}();
				}
			}
		}
		return $default;
	}

	/**
	 * Nastavi parametr na hodnotu.
	 * @param string
	 * @param mixed
	 * @return void
	 * @throws NotValidException
	 */
	final private function setValueHelper($name, $value, $rule)
	{
		if ($value === IEntity::DEFAULT_VALUE)
		{
			$value = $this->getDefaultValueHelper($name, $rule);
		}

		if ($rule['relationship'] === MetaData::ManyToOne OR $rule['relationship'] === MetaData::OneToOne)
		{
			if ($value instanceof IEntity)
			{
				if ($model = $this->getModel(false))
				{
					$repo = $model->getRepository($rule['relationshipParam']);
					$repo->attach($value);
				}
				else if ($model = $value->getModel(false))
				{
					$this->fireEvent('onAttachModel', NULL, $model);
				}
			}
			else if ($value !== NULL)
			{
				$id = (string) $value;
				$repo = $this->getModel()->getRepository($rule['relationshipParam']);
				$value = $repo->getById($id);
				if (!$value AND !isset($rule['types']['null']))
				{
					$type = implode('|',$rule['types']);
					throw new EntityNotFoundException("Entity($type) '$id' not found in `" . get_class($repo) . "` in ".get_class($this)."::\$$name");
				}
			}
		}
		else if (isset($rule['injection']))
		{
			if (!isset($this->values[$name]) OR !($this->values[$name] instanceof IEntityInjection))
			{
				$xValues = isset($this->values[$name]) ? $this->values[$name] : NULL;
				$tmp = $rule['injection']->invoke($this, $xValues);
				if ($xValues !== $value) $tmp->setInjectedValue($value);
			}
			else
			{
				$tmp = $this->values[$name];
				$tmp->setInjectedValue($value);
			}
			$value = $tmp;
		}

		if (isset($rule['enum']))
		{
			if (in_array($value, $rule['enum']['constants'], true)) {}
			else if (($tmp = array_search($value, $rule['enum']['constants'])) !== false)
			{
				$value = $rule['enum']['constants'][$tmp];
			}
			else if (isset($rule['types']['null']) AND $value === NULL)
			{
				$value = NULL;
			}
			else
			{
				throw new NotValidException([$this, $name, $rule['enum']['original'], $value]);
			}
		}
		if (!ValidationHelper::isValid($rule['types'], $value))
		{
			throw new NotValidException([$this, $name, "'" . implode('|', $rule['types']) . "'", $value]);
		}

		$oldValue = isset($this->values[$name]) ? $this->values[$name] : NULL;
		$this->values[$name] = $value;
		$this->valid[$name] = true;
		$this->changed[$name] = true;

		if ($rule['relationship'] === MetaData::ManyToOne OR $rule['relationship'] === MetaData::OneToOne)
		{
			$meta = $rule['relationshipParam'];
			if ($childParam = $meta->getChildParam())
			{
				$oldEntity = $oldValue;
				$newEntity = $value;
				$changed = false;
				if ($oldEntity instanceof IEntity AND $newEntity instanceof IEntity)
				{
					$changed = $oldEntity !== $newEntity;
				}
				else if ($newEntity instanceof IEntity AND isset($newEntity->id))
				{
					$changed = (string) $newEntity->id !== (string) $oldEntity;
				}
				else if ($newEntity instanceof IEntity)
				{
					if ($oldEntity !== NULL)
					{
						$repo = $this->getModel()->getRepository($meta->getRepository());
						$oldEntity = $repo->getById($oldEntity);
					}
					$changed = $oldEntity !== $newEntity;
				}
				else if ($newEntity === NULL)
				{
					if (!($oldEntity instanceof IEntity) AND $oldEntity !== NULL)
					{
						$repo = $this->getModel()->getRepository($meta->getRepository());
						$oldEntity = $repo->getById($oldEntity);
					}
					$changed = $oldEntity !== NULL;
				}

				if ($changed)
				{
					if (!($oldEntity instanceof IEntity) AND $oldEntity !== NULL)
					{
						$repo = $this->getModel()->getRepository($meta->getRepository());
						$oldEntity = $repo->getById($oldEntity);
					}
					if ($rule['relationship'] === MetaData::ManyToOne)
					{
						if ($oldEntity)
						{
							$oldEntity->{$childParam}->remove($this, 'handled by ManyToOne');
						}
						if ($newEntity)
						{
							$newEntity->{$childParam}->add($this);
						}
					}
					else if ($rule['relationship'] === MetaData::OneToOne)
					{
						if ($oldEntity AND $oldEntity->__isset($childParam) AND $oldEntity->__get($childParam) === $this)
						{
							$oldEntity->__set($childParam, NULL);
						}
						if ($newEntity AND (!$newEntity->__isset($childParam) OR $newEntity->__get($childParam) !== $this))
						{
							$newEntity->__set($childParam, $this);
						}
					}
				}
			}
		}
	}

	/**
	 * Call to undefined static method.
	 * @param string
	 * @param array
	 * @return mixed
	 */
	public static function __callStatic($name, $args)
	{
		// @codeCoverageIgnoreStart
		if (PHP_VERSION_ID === 50303)
		{
			// fix for bug 5.3.3 #52713; Generates a warning, because too slow.
			if (strncmp($name, 'get', 3) === 0 OR strncmp($name, 'is', 3) === 0 OR strncmp($name, 'set', 3) === 0)
			{
				$m = substr($name, 0, 1);
				$var = substr($name, 3);
				if ($m === 'i')
				{
					$m = 'g';
					$var = substr($name, 2);
				}
				if ($var{0} != '_') $var{0} = $var{0} | "\x20"; // lcfirst
				$message = "setValue('$var', \$value) instead of parent::$name(\$value)";
				if ($m === 'g')
				{
					$message = "getValue('$var') instead of parent::$name()";
				}
				trigger_error("php 5.3.3 bug #52713; Upgrade php or use \$this->$message", E_USER_WARNING);
				foreach (debug_backtrace() as $trace)
				{
					if (isset($trace['object']) AND $trace['object'] instanceof IEntity)
					{
						return $trace['object']->__call($name, $args);
					}
				}
			}
		}
		// @codeCoverageIgnoreEnd
		return parent::__callStatic($name, $args);
	}

}
