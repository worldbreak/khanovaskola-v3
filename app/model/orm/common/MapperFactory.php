<?php

namespace App\Model;

use App\Services\ElasticSearch;
use App\Services\Translator;
use Kdyby\Events\EventManager;
use Nette\DI\Container;
use Orm\AnnotationClassParser;
use Orm\IMapper;
use Orm\IRepository;


class MapperFactory extends \Orm\MapperFactory
{

	/** @var Container */
	private $container;

	public function __construct(AnnotationClassParser $parser, Container $container)
	{
		parent::__construct($parser);
		$this->container = $container;
	}


	/**
	 * @param IRepository $repository
	 * @internal param $IRepository
	 * @return IMapper
	 */
	public function createMapper(IRepository $repository)
	{
		$class = $this->getMapperClass($repository);
		/** @var Mapper $mapper */
		$mapper = new $class($repository);
		$traits = $this->getTraits($class);

		if (in_array('App\\Model\\ElasticSearchTrait', $traits))
		{
			/** @var ElasticSearch $elastic */
			$elastic = $this->container->getService('elastic');
			/** @var ElasticSearchTrait $mapper */
			$mapper->injectElasticSearch($elastic);
		}
		if (in_array('App\\Model\\TranslatorTrait', $traits))
		{
			/** @var Translator $translator */
			$translator = $this->container->getService('translator');
			/** @var TranslatorTrait $mapper */
			$mapper->injectTranslator($translator);
		}
		if (in_array('App\\Model\\EventManagerTrait', $traits))
		{
			/** @var EventManager $eventManager */
			$eventManager = $this->container->getByType('Kdyby\\Events\\EventManager');
			/** @var EventManagerTrait $mapper */
			$mapper->injectEventManager($eventManager);
		}

		$mapper->registerEvents($repository->getEvents());

		return $mapper;
	}

	private function getTraits($class)
	{
		$traits = class_uses($class);
		foreach (class_parents($class) as $parent)
		{
			$traits = array_merge($traits, class_uses($parent));
			if ($parent === 'App\Model\Mapper')
			{
				break;
			}
		}

		return $traits;
	}

}
