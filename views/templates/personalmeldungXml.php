<?xml version="1.0" encoding="UTF-8"?>
<Erhalter>
	<ErhKz><?php echo $personalmeldung->erhalter_kz ?></ErhKz>
	<MeldeDatum><?php echo $personalmeldung->meldedatum ?></MeldeDatum>
	<PersonalMeldung>
<?php foreach ($personalmeldung->personen as $person): ?>
		<Person>
			<PersonalNummer><?php echo $person->personalnummer ?></PersonalNummer>
			<Geschlecht><?php echo $person->geschlecht ?></Geschlecht>
			<Geburtsjahr><?php echo $person->geburtsjahr ?></Geburtsjahr>
			<StaatsangehoerigkeitCode><?php echo $person->staatsangehoerigkeit ?></StaatsangehoerigkeitCode>
			<HoechsteAbgeschlosseneAusbildung><?php echo $person->hoechste_abgeschlossene_ausbildung ?></HoechsteAbgeschlosseneAusbildung>
			<Habilitation><?php echo $person->habilitation ?></Habilitation>
		</Person>
<?php endforeach; ?>
	</PersonalMeldung>
</Erhalter>
