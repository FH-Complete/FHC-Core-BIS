<?php

class PersonalmeldungDate extends DateTime
{
	const START_TYPE = 's';
	const END_TYPE = 'e';

	public $startEndType;

	public function __construct($dateString, $startEndType)
	{
		parent::__construct($dateString);
		$this->startEndType = $startEndType;
	}

	public function compare(PersonalmeldungDate $personalmeldungDate)
	{
		if ($this < $personalmeldungDate) return -1;
		if ($this > $personalmeldungDate) return 1;
		if ($this == $personalmeldungDate)
		{
			if ($this->startEndType == self::START_TYPE) return -1;
			if ($this->startEndType == self::END_TYPE) return 1;
		};
		return 0;
	}
}
