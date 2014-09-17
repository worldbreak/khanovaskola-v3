<?php

namespace App\Components\Forms;

use App\Components\Controls\Comments;
use App\Components\FormControl;
use App\InvalidStateException;
use App\Models\Orm\RepositoryContainer;
use App\Models\Rme;
use App\Presenters\Content;


class Comment extends Form
{

	/**
	 * @var RepositoryContainer
	 * @inject
	 */
	public $orm;

	/**
	 * @var Content
	 */
	public $content;

	public function setup()
	{
		$this->addTextArea('text')
			->addRule($this::FILLED, 'text.missing')
			->addRule($this::MIN_LENGTH, 'text.short', 15);

		$this->addSubmit();
	}

	protected function attached($presenter)
	{
		parent::attached($presenter);

		if (! $this->presenter instanceof Content)
		{
			throw new InvalidStateException;
		}

		/** @var FormControl $fc */
		$fc = $this->parent;
		if (! $fc->parent instanceof Comments)
		{
			throw new InvalidStateException;
		}

		$this->content = $fc->parent->getContent();
	}

	public function onSuccess()
	{
		$v = $this->getValues();

		/** @var Content $presenter */
		$presenter = $this->presenter;

		$comment = new Rme\Comment();
		$comment->author = $presenter->getUserEntity();
		$comment->text = $v->text;
		$comment->content = $this->content;

		$this->orm->comments->attach($comment);
		$this->orm->flush();

		$presenter->flashSuccess('added');
		$presenter->redirect('this');
	}
}
