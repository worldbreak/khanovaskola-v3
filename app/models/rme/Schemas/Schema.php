<?php

namespace App\Models\Rme;

use App\InvalidStateException;
use App\Models\Orm\Entity;
use Orm\OneToMany as OtM;


/**
 * @property string                  $name
 * @property string                  $description
 * @property array                   $layout             json
 * @property OtM|BlockSchemaBridge[] $blockSchemaBridges {1:m blockSchemaBridges $schema}
 * @property Subject                 $subject            {m:1 subjects $schemas}
 * @property User                    $author             {m:1 users $schemasAuthored}
 * @property OtM|CompletedBlock[]    $completedBlocks    {1:m completedBlocks $schema}
 * @property OtM|CompletedContent[]  $completedContents  {1:m completedContents $schema}
 *
 * @property Block[]                 $blocks             {ignore}
 */
class Schema extends Entity
{

	/**
	 * @return Block[]
	 */
	public function getBlocks()
	{
		/** @var OtM $o */
		$o = $this->getValue('blockSchemaBridges');
		$bridges = $o->get()->orderBy('position', 'ASC');

		foreach ($bridges as $bridge)
		{
			yield $bridge->block;
		}
	}

	/**
	 * @return int seconds
	 */
	public function getDuration()
	{
		$duration = 0;
		foreach ($this->blocks as $block)
		{
			$duration += $block->getDuration();
		}
		return $duration;
	}

	/**
	 * @return Block
	 */
	public function getFirstBlock()
	{
		foreach ($this->blocks as $block)
		{
			return $block;
		}
		throw new InvalidStateException;
	}

	public function contains(Block $block)
	{
		return $this->getValue('blockSchemaBridges')->get()->findBy(['blockId' => $block->id])->count();
	}

	/**
	 * @param User $user
	 * @return float 0..100
	 */
	public function getCompletedPercent(User $user)
	{
		return $this->model->completedContents->getCompletedPercent($user, $this);
	}

}
