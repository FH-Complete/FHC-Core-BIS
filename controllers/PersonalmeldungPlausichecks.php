<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

class PersonalmeldungPlausichecks extends Auth_Controller
{
	const GENERIC_ISSUE_OCCURED_TEXT = 'Issue aufgetreten';

	private $_fehlerLibMappings = array(
		'aktiveMitarbeiterOhneDienstverhaeltnis' => 'AktiveMitarbeiterOhneDienstverhaeltnis',
		'aktiveFixeMitarbeiterOhneDienstverhaeltnis' => 'AktiveFixeMitarbeiterOhneDienstverhaeltnis',
		'hauptberufcodeOhneLehreVerwendung' => 'HauptberufcodeOhneLehreVerwendung',
		'inaktiveMitarbeiterMitDienstverhaeltnis' => 'InaktiveMitarbeiterMitDienstverhaeltnis',
		'lehrauftragOhneDienstverhaeltnis' => 'LehrauftragOhneDienstverhaeltnis',
		'mitarbeiterMitDienstverhaeltnisOhneVerwendung' => 'MitarbeiterMitDienstverhaeltnisOhneVerwendung',
		'mitarbeiterOhneStammdaten' => 'MitarbeiterOhneStammdaten',
		'mitarbeiterUngueltigeSemesterwochenstunden' => 'MitarbeiterUngueltigeSemesterwochenstunden',
		'mitarbeiterUngueltigesGeburtsjahr' => 'MitarbeiterUngueltigesGeburtsjahr',
		'mitarbeiterUngueltigesVzae' => 'MitarbeiterUngueltigesVzae'
	);

	public function __construct()
	{
		parent::__construct(
			array(
				'index' => array('admin:r'),
				'runChecks' => array('admin:r')
			)
		);

		// Load libraries
		$this->load->library('issues/PlausicheckProducerLib', array('app' => 'personalmeldung', 'extensionName' => 'FHC-Core-BIS'));
		$this->load->library('WidgetLib');

		// Load models
		$this->load->model('system/Fehler_model', 'FehlerModel');
		$this->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
	}

	/*
	 * Get data for filtering the plausichecks and load the view.
	 */
	public function index()
	{
		$this->load->view('extensions/FHC-Core-BIS/plausichecks');
	}

	/**
	 * Initiate plausichecks run.
	 */
	public function runChecks()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');
		//$fehler_kurzbz = $this->input->get('fehler_kurzbz');

		// issues array for passing issue texts
		$allIssues = array();

		// get the data returned by Plausicheck
		foreach ($this->_fehlerLibMappings as $fehler_kurzbz => $libName)
		{
			// get Text and fehlercode of the Fehler
			$this->FehlerModel->addSelect('fehlercode, fehlertext, fehlertyp_kurzbz');
			$fehlerRes = $this->FehlerModel->loadWhere(array('fehler_kurzbz' => $fehler_kurzbz));

			if (isError($fehlerRes)) $this->terminateWithJsonError(getError($fehlerRes));

			// do not check error if no data
			if (!hasData($fehlerRes)) continue;

			// get the error data
			$fehler = getData($fehlerRes)[0];

			// initialize issue array
			$allIssues[$fehler_kurzbz] = array('fehlercode' => $fehler->fehlercode, 'data' => array());

			// execute the check
			$plausicheckRes = $this->plausicheckproducerlib->producePlausicheckIssue(
				$libName,
				$fehler_kurzbz,
				array(
					'studiensemester_kurzbz' => $studiensemester_kurzbz
				)
			);

			if (isError($plausicheckRes)) $this->terminateWithJsonError(getError($plausicheckRes));

			if (hasData($plausicheckRes))
			{
				$plausicheckData = getData($plausicheckRes);

				foreach ($plausicheckData as $plausiData)
				{
					// get the data needed for issue production
					$fehlertext_params = isset($plausiData['fehlertext_params']) ? $plausiData['fehlertext_params'] : null;

					// optionally replace fehler parameters in text, output the fehlertext
					if (!isEmptyString($fehler->fehlertext))
					{
						$fehlercode = $fehler->fehlercode;
						$fehlerText = $fehler->fehlertext;
						$fehlerTyp = $fehler->fehlertyp_kurzbz;

						if (!isEmptyArray($fehlertext_params))
						{
							// replace placeholder with params, if present
							if (numberOfElements($fehlertext_params) != substr_numberOfElements($fehlerText, '%s'))
								$this->terminateWithJsonError('Wrong number of parameters for Fehlertext, fehler_kurzbz ' . $fehler_kurzbz);

							$fehlerText = vsprintf($fehlerText, $fehlertext_params);
						}

						$issueObj = new StdClass();
						$issueObj->fehlertext = $fehlerText;
						$issueObj->type = $fehlerTyp;
						$allIssues[$fehler_kurzbz]['data'][] = $issueObj;
					}
					else // if no issue text found, use generic text
					{
						$fehlerText = self::GENERIC_ISSUE_OCCURED_TEXT;
					}
				}
			}
		}

		$this->outputJsonSuccess($allIssues);
	}
}
