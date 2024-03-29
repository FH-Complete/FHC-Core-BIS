<?php

/**
 * Represents a date as used in Personalmeldung.
 * Is a normal date, but additionally has type (start date or end date)
 */
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

	/**
	 * Compares this date with another date.
	 * @param $personalmeldungDate date to compare this date with
	 * @return int -1 if this date is smaller, 1 if greater, 0 if equal
	 */
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
