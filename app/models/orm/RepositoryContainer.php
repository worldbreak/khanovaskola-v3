<?php

namespace App\Models\Orm;

use App\Models\Rme;
use Clevis\Skeleton;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\DI\Container;


/**
 * @property-read Rme\AnswersRepository $answers
 * @property-read Rme\BadgesRepository $badges
 * @property-read Rme\BadgeUserBridgesRepository $badgeUserBridges
 * @property-read Rme\BlueprintsRepository $blueprints
 * @property-read Rme\CommentsRepository $comments
 * @property-read Rme\GistsRepository $gists
 * @property-read Rme\PathsRepository $paths
 * @property-read Rme\TagsRepository $tags
 * @property-read Rme\UsersRepository $users
 * @property-read Rme\VideosRepository $videos
 * @property-read Rme\VideoViewsRepository $videoViews
 */
class RepositoryContainer extends Skeleton\Orm\RepositoryContainer
{

	/** @var \Nette\Caching\Cache */
	protected $cache;

	public function __construct($containerFactory = NULL, Container $container = NULL)
	{
		parent::__construct($containerFactory);
		/** @var IStorage $storage */
		$storage = $container->getService('cacheStorage');
		$this->cache = new Cache($storage);
	}

	public function getByEntityName($name)
	{
		$alias = [
			'blueprint' => 'blueprints',
			'video' => 'videos',
		];
		return $this->{$alias[strToLower($name)]};
	}

	/**
	 * @return Cache
	 */
	public function getCache()
	{
		return $this->cache;
	}

}