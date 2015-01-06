<?php

namespace App\Presenters;

use App\Models\Rme;


final class Homepage extends Presenter
{

	public function renderDefault()
	{
		$this->setCacheControlPublic();

		$subjects = $this->orm->subjects->findAll()->applyLimit(6)->fetchAll();
		$this->template->firstSubject = array_shift($subjects);
		$this->template->secondThirdSubject = array_filter([array_shift($subjects), array_shift($subjects)]);
		$this->template->moreSubjects = array_filter([array_shift($subjects), array_shift($subjects), array_shift($subjects)]);

		$count = $this->orm->contents->findAll()->count();
		$this->template->titleVideoCount = round($count / 100) * 100;
	}

}
