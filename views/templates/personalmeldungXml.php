<?xml version="1.0" encoding="UTF-8"?>
<Erhalter>
	<ErhKz><?php echo $personalmeldung->erhalter_kz ?></ErhKz>
	<MeldeDatum><?php echo $personalmeldung->meldedatum ?></MeldeDatum>
	<PersonalMeldung>
<?php foreach ($personalmeldung->personen as $person): ?>
		<Person>
			<PersonalNummer><?php echo $person->personalnummer ?></PersonalNummer>
			<Geschlecht><?php echo $person->geschlecht ?></Geschlecht>
<?php if ($person->geschlecht == 'x'): ?>
				<GeschlechtX><?php echo $person->geschlechtX ?></GeschlechtX>
<?php endif; ?>
			<Geburtsjahr><?php echo $person->geburtsjahr ?></Geburtsjahr>
			<StaatsangehoerigkeitCode><?php echo $person->staatsangehoerigkeit ?></StaatsangehoerigkeitCode>
			<HoechsteAbgeschlosseneAusbildung><?php echo $person->hoechste_abgeschlossene_ausbildung ?></HoechsteAbgeschlosseneAusbildung>
			<Habilitation><?php echo $person->habilitation ?></Habilitation>
<?php if (isset($person->hauptberufcode) && $person->hauptberuflich === false) : ?>
			<HauptberufCode><?php echo $person->hauptberufcode ?></HauptberufCode>
<?php endif; ?>
<?php foreach ($person->verwendungen as $verwendung):
$vzae = $verwendung->vzae < 0 ? $verwendung->vzae : number_format($verwendung->vzae, 2, '.', '');
?>
			<Verwendung>
				<VerwendungsCode><?php echo $verwendung->verwendung_code ?></VerwendungsCode>
				<BeschaeftigungsArt1><?php echo $verwendung->ba1code ?></BeschaeftigungsArt1>
				<BeschaeftigungsArt2><?php echo $verwendung->ba2code ?></BeschaeftigungsArt2>
				<BeschaeftigungsAusmassVZAE><?php echo $vzae ?></BeschaeftigungsAusmassVZAE>
				<BeschaeftigungsAusmassJVZAE><?php echo number_format($verwendung->jvzae, 2, '.', '') ?></BeschaeftigungsAusmassJVZAE>
			</Verwendung>
<?php endforeach; ?>
<?php foreach ($person->funktionen as $funktion): ?>
			<Funktion>
				<FunktionsCode><?php echo $funktion->funktionscode ?></FunktionsCode>
<?php if (isset($funktion->besondereQualifikationCode)): ?>
				<BesondereQualifikationCode><?php echo $funktion->besondereQualifikationCode ?></BesondereQualifikationCode>
<?php endif; ?>
<?php foreach($funktion->studiengang as $studiengang): //TODO: need to check for codes 5 and 7 or not ?>
				<Studiengang>
				<StgKz><?php echo $studiengang ?></StgKz>
				</Studiengang>
<?php endforeach; ?>
			</Funktion>
<?php endforeach; ?>
<?php foreach ($person->lehre as $lehre): ?>
			<Lehre>
				<StgKz><?php echo $lehre->StgKz ?></StgKz>
				<SommersemesterSWS><?php echo $lehre->SommersemesterSWS ?></SommersemesterSWS>
				<WintersemesterSWS><?php echo $lehre->WintersemesterSWS ?></WintersemesterSWS>
			</Lehre>
<?php endforeach; ?>
		</Person>
<?php endforeach; ?>
	</PersonalMeldung>
</Erhalter>