<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

class PersonalmeldungFileVergleich extends Auth_Controller
{
	const DEFAULT_FILE_PATH = 'vilesci/bis/bisdaten/bismeldung_mitarbeiter.xml';
	const VZAE_DIFF_THRESHOLD = 20;
	const INCLUDE_NEGATIVE_VZAE = false;

	private $_messages = array(
		'mitarbeiter' => array(),
		'fehlender_verwendung_code' => array(),
		'falscher_verwendung_code' => array(),
		'ba1code' => array(),
		'ba2code' => array(),
		'vzae' => array(),
		'jvzae' => array()
	);

	public function __construct()
	{
		parent::__construct(
			array(
				'index' => array('admin:r'),
				'compareFile' => array('admin:r')
			)
		);

		$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungLib');
	}

	/*
	 * Get data for filtering the plausichecks and load the view.
	 */
	public function index()
	{
		$this->load->view('extensions/FHC-Core-BIS/filevergleich', $this->personalmeldungdataprovisionlib->getStudiensemesterData());
	}

	/**
	 * Initiate comparison of xml personalmeldung file with personal from database.
	 */
	public function compareFile()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		$this->load->view('extensions/FHC-Core-BIS/filevergleichOutput', $this->_getFileComparisonData($studiensemester_kurzbz));
	}

	/**
	 * Get data for comparing data of old file Personalmeldung file with new PV21 data.
	 * @param studiensemester_kurzbz
	 * @return array
	 */
	private function _getFileComparisonData($studiensemester_kurzbz)
	{
		$fileComparisonData = array();

		$doc = new DOMDocument();
		$loadres = $doc->loadXML(file_get_contents(DOC_ROOT.self::DEFAULT_FILE_PATH));

		if (!$loadres) show_error('error when parsing xml string');

		$fileComparisonData['meldeYear'] = substr($studiensemester_kurzbz, -4);

		$oldMitarbeiter = $doc->getElementsByTagName('Person');

		$mitarbeiterRes = $this->personalmeldunglib->getMitarbeiterData($studiensemester_kurzbz);

		if (isError($mitarbeiterRes)) show_error(getError($mitarbeiterRes));

		if (hasData($mitarbeiterRes))
		{
			$mitarbeiter = getData($mitarbeiterRes);
			$newMitarbeiter = array();

			foreach ($mitarbeiter as $ma)
			{
				$newMitarbeiter[$ma->personalnummer] = $ma;
			}

			$mitarbeiterMissingInFile = array_merge(array(), $newMitarbeiter);

			$fileComparisonData['fileMitarbeiterCount'] = $oldMitarbeiter->length;
			$fileComparisonData['mitarbeiterCount'] = count($newMitarbeiter);

			$oldConvertedMitarbeiter = $this->_convertMitarbeiterArr($oldMitarbeiter);

			// get sums of old and new Mitarbeiter
			$fileComparisonData['fileMitarbeiterSums'] = $this->personalmeldunglib->getPersonalmeldungSums($oldConvertedMitarbeiter);
			$fileComparisonData['mitarbeiterSums'] = $this->personalmeldunglib->getPersonalmeldungSums($mitarbeiter);

			foreach ($oldConvertedMitarbeiter as $oldMa)
			{
				$personalnummer = $oldMa->personalnummer;
				$oldVerwendungen = $oldMa->verwendungen;
				$oldVerwendungCodes = array();


				unset($mitarbeiterMissingInFile[$personalnummer]);
				if (!isset($newMitarbeiter[$personalnummer]))
				{
					$this->_messages['mitarbeiter'][] =
						$this->_getMsgObj("Mitarbeiter fehlt in PV21, Personalnummer $personalnummer", null, $personalnummer);
					continue;
				}

				$newMa = $newMitarbeiter[$personalnummer];

				// compare Verwendungen
				$oldVerwendungCodes = array_unique(array_column($oldVerwendungen, 'verwendung_code'));
				$newVerwendungCodes = array_unique(array_column($newMa->verwendungen, 'verwendung_code'));

				// get unknown Verwendungen
				if (count($newVerwendungCodes) == 1 && is_null($newVerwendungCodes[0]))
				{
					$this->_messages['fehlender_verwendung_code'][] = $this->_getMsgObj(
						"Fehlender Verwendung Code",
						$newMa->uid
					);
				}

				$oldBa1Codes = array_unique(array_column($oldVerwendungen, 'ba1code'));
				$newBa1Codes = array_unique(array_column($newMa->verwendungen, 'ba1code'));

				$oldBa2Codes = array_unique(array_column($oldVerwendungen, 'ba2code'));
				$newBa2Codes = array_unique(array_column($newMa->verwendungen, 'ba2code'));

				$oldVzae = array_sum(array_unique(array_column($oldVerwendungen, 'vzae')));
				$newVzae = array_sum(array_unique(array_column($newMa->verwendungen, 'vzae')));

				$oldJvzae = array_sum(array_unique(array_column($oldVerwendungen, 'jvzae')));
				$newJvzae = array_sum(array_unique(array_column($newMa->verwendungen, 'jvzae')));

				sort($oldVerwendungCodes);
				sort($newVerwendungCodes);
				sort($oldBa1Codes);
				sort($newBa1Codes);
				sort($oldBa2Codes);
				sort($newBa2Codes);

				if ($oldVerwendungCodes != $newVerwendungCodes)
				{
					$this->_messages['falscher_verwendung_code'][] = $this->_getMsgObj(
						"Unterschiedliche Verwendung Codes, ".implode (", ", $oldVerwendungCodes)." (File) VS ".implode(", ", $newVerwendungCodes),
						$newMa->uid
					);
				}

				if ($oldBa1Codes != $newBa1Codes)
				{
					$this->_messages['ba1code'][] = $this->_getMsgObj(
						"Unterschiedliche BA1 Codes, ".implode (", ", $oldBa1Codes)." (File) VS ".implode(", ", $newBa1Codes),
						$newMa->uid
					);
				}

				if ($oldBa2Codes != $newBa2Codes)
				{
					$this->_messages['ba2code'][] = $this->_getMsgObj(
						"Unterschiedliche BA2 Codes, ".implode (", ", $oldBa2Codes)." (File) VS ".implode(", ", $newBa2Codes),
						$newMa->uid
					);
				}

				//~ $vzaeRelation = abs($oldVzae - $newVzae) / ($oldVzae == 0 ? 1 : $oldVzae);

				//~ if ($vzaeRelation >= self::VZAE_RELATION_THRESHOLD)
				//~ {
					//~ $this->_messages['vzae'][] = $this->_getMsgObj(
						//~ "VZAE Abweichung zu groß: ".round($oldVzae, 2)." VS ".round($newVzae, 2)." (".number_format($vzaeRelation * 100, 2)."% Abweichung)",
						//~ $newMa->uid,
						//~ $vzaeRelation
					//~ );
				//~ }

				//~ $jvzaeRelation = abs($oldJvzae - $newJvzae) / ($oldJvzae == 0 ? 1 : $oldJvzae);

				//~ if ($jvzaeRelation >= self::VZAE_RELATION_THRESHOLD)
				//~ {
					//~ $this->_messages['jvzae'][] =  $this->_getMsgObj(
						//~ "JVZAE Abweichung zu groß: ".round($oldJvzae, 2)." VS ".round($newJvzae, 2)." (".number_format($jvzaeRelation * 100, 2)."% Abweichung)",
						//~ $newMa->uid,
						//~ $jvzaeRelation
					//~ );
				//~ }

				$vzaeDiff = abs($oldVzae - $newVzae);

				if ($vzaeDiff >= self::VZAE_DIFF_THRESHOLD && (self::INCLUDE_NEGATIVE_VZAE || ($newVzae > 0 && $oldVzae > 0)))
				{
					$this->_messages['vzae'][] = $this->_getMsgObj(
						"VZAE Abweichung zu groß: ".round($oldVzae, 2)." (File) VS ".round($newVzae, 2)." (".number_format($vzaeDiff, 2)." Abweichung)",
						$newMa->uid,
						$vzaeDiff
					);
				}

				$jvzaeDiff = abs($oldJvzae - $newJvzae);

				if ($jvzaeDiff >= self::VZAE_DIFF_THRESHOLD)
				{
					$this->_messages['jvzae'][] = $this->_getMsgObj(
						"JVZAE Abweichung zu groß: ".round($oldJvzae, 2)." (File) VS ".round($newJvzae, 2)." (".number_format($jvzaeDiff, 2)." Abweichung)",
						$newMa->uid,
						$jvzaeDiff
					);
				}
			}

			foreach ($mitarbeiterMissingInFile as $personalnummer => $ma)
			{
				$this->_messages['mitarbeiter'][] =
					$this->_getMsgObj("Mitarbeiter fehlt in File, Personalnummer $personalnummer", null, $personalnummer);
			}

			foreach ($this->_messages as $key => $msgArr)
			{
				usort($this->_messages[$key], function($a, $b)
				{
					if (is_string($a->sortKey) && is_string($b->sortKey))
						return strcmp($a->sortKey, $b->sortKey);

					if (is_numeric($a->sortKey) && is_numeric($b->sortKey))
						return $b->sortKey - $a->sortKey;

					return 0;
				});
			}

			$fileComparisonData['messages'] = $this->_messages;
		}

		return $fileComparisonData;
	}

	/**
	 * Get message object.
	 * @param message the message text
	 * @param uid
	 * @param sortKey for sorting the messages
	 * @return object
	 */
	private function _getMsgObj($message, $uid = '', $sortKey = '')
	{
		$msgObj = new stdClass();
		$msgObj->message = $message;
		$msgObj->uid = $uid;
		$msgObj->sortKey = isEmptyString($sortKey) ? $uid : $sortKey;

		return $msgObj;
	}

	/**
	 * Convert Mitarbeiter array from xml file object to normal PHP object
	 * @param $mitarbeiter
	 * @return array with converted mitarbeiter data for comparison
	 */
	private function _convertMitarbeiterArr($mitarbeiter)
	{
		$convertedMitarbeiterArr = array();
		foreach ($mitarbeiter as $oldMa)
		{
			$convMa = new stdClass();

			$convMa->verwendungen = array();
			$convMa->lehre = array();
			$convMa->funktionen = array();

			foreach ($oldMa->childNodes as $node)
			{
				$nodeValue = $node->nodeValue;
				switch($node->nodeName)
				{
					case 'PersonalNummer':
						$convMa->personalnummer = $nodeValue;
						break;
					case 'Verwendung':
						$verwendung = new stdClass();
						foreach ($node->childNodes as $verwendungValue)
						{
							switch($verwendungValue->nodeName)
							{
								case 'BeschaeftigungsArt1':
									$propName = 'ba1code';
									break;
								case 'BeschaeftigungsArt2':
									$propName = 'ba2code';
									break;
								case 'VerwendungsCode':
									$propName = 'verwendung_code';
									break;
								case 'BeschaeftigungsAusmassVZAE':
									$propName = 'vzae';
									break;
								case 'BeschaeftigungsAusmassJVZAE':
									$propName = 'jvzae';
									break;
								default:
									continue 2;
							}
							$verwendung->{$propName} = $verwendungValue->nodeValue;
						}
						$convMa->verwendungen[] = $verwendung;
						break;
					case 'Lehre':
						$lehre = new stdClass();
						foreach ($node->childNodes as $lehreValue)
						{
							switch($lehreValue->nodeName)
							{
								case 'SommersemesterSWS':
								case 'WintersemesterSWS':
									$propName = $lehreValue->nodeName;
									break;
								default:
									continue 2;
							}
							$lehre->{$propName} = $lehreValue->nodeValue;
						}
						$convMa->lehre[] = $lehre;
						break;
					case 'Funktion':
						$funktion = new stdClass();
						foreach ($node->childNodes as $funktionValue)
						{
							switch($funktionValue->nodeName)
							{
								case 'FunktionsCode':
									$propName = 'funktionscode';
									break;
								default:
									continue 2;
							}
							$funktion->{$propName} = $funktionValue->nodeValue;
						}
						$convMa->funktionen[] = $funktion;
						break;
				}
			}
			$convertedMitarbeiterArr[] = $convMa;
		}

		return $convertedMitarbeiterArr;
	}
}
