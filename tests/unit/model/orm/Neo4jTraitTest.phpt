<?php

namespace Tests\App\Mapper;

use App\Rme\RepositoryContainer;
use App\Rme\Tag;
use App\Rme\Video;
use App\Services\Neo4j;
use Nette;
use Tester;
use Tester\Assert;


$container = require __DIR__ . '/../../bootstrap.php';


class Neo4jTraitTest extends Tester\TestCase
{

	/** @var Neo4j */
	private $neo4j;

	/** @var RepositoryContainer */
	private $orm;

	private $container;

	function __construct(Nette\DI\Container $container)
	{
		$this->container = $container;
	}

	function setUp()
	{
		$this->neo4j = $this->container->getService('neo4j');
		$this->orm = $this->container->getService('orm');
	}

	function testCrud()
	{
		$video = new Video;
		$video->title = 'Dummy video';
		$video->description = 'Dummy desc';
		$video->youtubeId = 'abcedfg';

		$tag = new Tag;
		$tag->title = 'Dummy tag';

		$this->orm->tags->attach($tag);
		$this->orm->videos->attach($video);
		$this->orm->flush();

		$video->addTag($tag);

		$found = $this->orm->videos->findByTag($tag);
		Assert::same(1, $found->count());
		/** @var Video $foundVideo */
		$foundVideo = $found->fetch();
		Assert::same($video->id, $foundVideo->id);
	}

}

$test = new Neo4jTraitTest($container);
$test->run();
