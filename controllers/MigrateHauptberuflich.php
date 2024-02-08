<?php
/*
 * Job zur einmaligen Migration der Hauptberufe von bis.tbl_bisverwendung nach extension.tbl_bis_hauptberuf
 *
 * Aufruf fuer Alle
 * php index.ci.php system/MigrateHauptberuflich/index
 */

if (! defined('BASEPATH')) exit('No direct script access allowed');

class MigrateHauptberuflich extends CLI_Controller
{
	/**
	 * Hauptberuflich wird von bis.tbl_bisverwendung in die neue Tabelle extension.tbl_bis_hauptberuf uebertragen
	 * Eintraege bei denen das Von/Bis Datum nicht korrekt ist werden uebersprungen
	 */
	public function index()
	{
		$this->load->model('codex/Bisverwendung_model','BisverwendungModelAlt');
		$this->load->model('extensions/FHC-Core-BIS/personalmeldung/BisHauptberuf_model','BisHauptberufModel');

		$resultHauptberuflich = $this->BisverwendungModelAlt->loadWhere("hauptberuflich=false");

		if(isSuccess($resultHauptberuflich) && hasData($resultHauptberuflich))
		{
			$data = getData($resultHauptberuflich);

			foreach($data as $row)
			{
				if(!($row->beginn=='' && $row->ende=='')
					&&
					(
					($row->beginn <= $row->ende)
					|| ($row->ende == '' && $row->beginn != '')
				 	|| ($row->beginn == '' && $row->ende!='')
					)
				)
				{
					$this->BisHauptberufModel->insert(
						array(
						'mitarbeiter_uid' =>$row->mitarbeiter_uid,
						'hauptberuflich' => $row->hauptberuflich,
						'hauptberufcode' => $row->hauptberufcode,
						'von' => ($row->beginn != '' ? $row->beginn : $row->ende), // Wenn der Beginn leer ist, wird er auf das Ende gesetzt
						'bis' => $row->ende,
						'insertamum' => $row->insertamum,
						'insertvon' => $row->insertvon,
						'updateamum' => $row->updateamum,
						'updatevon' => $row->updatevon
						)
					);
				}
				else
				{
					echo "\nEintrag bei Person $row->mitarbeiter_uid wird uebersprungen da Datum inkorrekt: $row->beginn - $row->ende";
				}
			}
		}
	}
}
