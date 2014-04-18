<?php

namespace App\Presenters;

use App\Model;
use Nette;
use Nette\Diagnostics\Debugger;


final class ErrorPresenter extends BasePresenter
{

	/**
	 * @param Exception
	 * @return void
	 */
	public function renderDefault($exception)
	{
		if ($exception instanceof Nette\Application\BadRequestException)
		{
			$code = $exception->getCode();
			// load template 403.latte or 404.latte or ... 4xx.latte
			$this->setView(in_array($code, [403, 404, 405, 410, 500]) ? $code : '4xx');
			// log to access.log
			Debugger::log("HTTP code $code: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}", 'access');

		}
		else
		{
			$this->setView('500'); // load template 500.latte
			Debugger::log($exception, Debugger::ERROR); // and log exception
		}

		if ($this->isAjax())
		{ // AJAX request? Note this error in payload.
			$this->payload->error = TRUE;
			$this->terminate();
		}
	}

}
