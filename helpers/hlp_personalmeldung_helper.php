<?php

function getDateData($studiensemester_kurzbz)
{
	if (!checkStudiensemester($studiensemester_kurzbz)) return error("Invalid Studiensemester, must be Sommersemester");

	$dateData = array();

	$ci =& get_instance(); // get CI instance
	$ci->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');

	// get Studiensemester of Meldung
	$dateData['studiensemester_kurzbz'] = $studiensemester_kurzbz;

	$ci->StudiensemesterModel->addSelect('start');
	$studiensemesterRes = $ci->StudiensemesterModel->load($studiensemester_kurzbz);

	if (!hasData($studiensemesterRes)) return error('Studiensemester nicht gefunden');

	$studiensemesterData = getData($studiensemesterRes)[0];

	$studiensemesterStart = $studiensemesterData->start;

	// get Year of the semester and Bismeldung
	$dateData['studiensemesterYear'] = date('Y', strtotime($studiensemesterStart));
	$dateData['bismeldungYear'] = $dateData['studiensemesterYear'] - 1;

	// set start and end of year
	$dateData['yearStart'] = new DateTime($dateData['bismeldungYear']. '-01-01');
	$dateData['yearEnd'] = new DateTime($dateData['bismeldungYear']. '-12-31');

	// set days in year
	$dateData['daysInYear'] = $dateData['yearEnd']->diff($dateData['yearStart'])->days + 1;
	$dateData['weeksInYear'] = $dateData['daysInYear'] / 7;

	$winterSemesterRes = $ci->StudiensemesterModel->getPreviousFrom($dateData['studiensemester_kurzbz']);
	if (isError($winterSemesterRes)) return $winterSemesterRes;
	$dateData['winterSemesterImMeldungsjahr'] = hasData($winterSemesterRes)? getData($winterSemesterRes)[0]->studiensemester_kurzbz : null;

	$sommerSemesterRes = $ci->StudiensemesterModel->getPreviousFrom($dateData['winterSemesterImMeldungsjahr']);
	if (isError($sommerSemesterRes)) return $sommerSemesterRes;
	$dateData['sommerSemesterImMeldungsjahr'] = hasData($sommerSemesterRes)? getData($sommerSemesterRes)[0]->studiensemester_kurzbz : null;

	return success($dateData);
}

function checkStudiensemester($studiensemester_kurzbz)
{
	return !isEmptyString($studiensemester_kurzbz) && mb_strstr($studiensemester_kurzbz,'SS');
}
