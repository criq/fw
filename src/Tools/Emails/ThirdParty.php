<?php

namespace Katu\Tools\Emails;

abstract class ThirdParty extends \Katu\Tools\Emails\Email
{
	public $recipientVariables = [];
	public $template;
	public $variables = [];

	abstract public function send();

	public function setTemplate($template)
	{
		$this->template = $template;

		return $this;
	}

	public function addAttachment($file, $params = [])
	{
		$this->attachments[] = [
			'file' => new \Katu\Files\File($file),
			'name' => isset($params['name']) ? $params['name'] : null,
			'cid'  => isset($params['cid']) ? $params['cid'] : null,
		];

		return $this;
	}

	public function setVariable($name, $value)
	{
		if (trim($name)) {
			$this->variables[$name] = $value;
		}

		return $this;
	}

	public function setRecipientVariable($emailAddress, $name, $value)
	{
		foreach (static::resolveEmailAddress($emailAddress) as $emailAddress) {
			if (trim($name)) {
				$this->recipientVariables[$emailAddress][$name] = $value;
			}
		}

		return $this;
	}
}