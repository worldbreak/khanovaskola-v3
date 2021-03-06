<?php

namespace App\Components\Forms;

use App\Models\Rme;
use App\Models\Structs\EntityPointer;
use App\Models\Tasks;
use Kdyby\RabbitMq\Connection;
use Nette\Forms\Controls\BaseControl;


class Video extends EditorForm
{

	/**
	 * @var Connection
	 * @inject
	 */
	public $queue;

	public function setup()
	{
		$this->addText('title')
			->setRequired('title.missing');
		$this->addText('description')
			->setRequired('description.missing');
		$this->addText('youtubeId')
			->setRequired('youtubeId.missing');
		$this->addCheckbox('visible');

		$this->addSubmit();
	}

	protected function process()
	{
		$v = $this->getValues();

		$video = $this->presenter->video;
		$mode = 'edited';
		if (!$video)
		{
			$video = new Rme\Video();
			$this->orm->contents->attach($video);
			$mode = 'added';
		}

		$old = $this->orm->contents->getVideoByYoutubeId($v->youtubeId);
		if ($old && ($mode === 'added' || $old->id !== $video->id))
		{
			/** @var self|BaseControl[] $this */
			$this['youtubeId']->addError('Toto youtubeId už v databázi existuje.');
			return FALSE;
		}

		$video->title = $v->title;
		$video->description = $v->description;
		$video->youtubeId = $v->youtubeId;
		$video->hidden = !$v->visible;

		$this->orm->flush();

		$producer = $this->queue->getProducer('updateVideo');
		$producer->publish(serialize([
			'schema' => new EntityPointer($video),
		]));

		$this->presenter->flashSuccess("editor.$mode.video");

		$block = $this->presenter->block;
		$schema = $this->presenter->schema;
		$this->presenter->redirect('this', [
			'videoId' => $video->id,
			'blockId' => $block ? $block->id : NULL,
			'schemaId' => $schema ? $schema->id : NULL,
		]);

		return TRUE;
	}
}
