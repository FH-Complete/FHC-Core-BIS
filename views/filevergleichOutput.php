<?php
	$includesArray = array(
		'title' => 'Filevergleich',
		'bootstrap5' => true,
		'fontawesome6' => true,
		'navigationcomponent' => true,
		'customCSSs' => array(
			'vendor/vuejs/vuedatepicker_css/main.css',
			'public/extensions/FHC-Core-BIS/css/FilevergleichOutput.css'
		)
	);

	$this->load->view('templates/FHC-Header', $includesArray);
?>
	<div id="main">
		<div id="content">
			<header>
				<h1 class="h2 fhc-hr">Personalmeldung File Daten Vergleich</h1>
			</header>
			<br>
			Meldejahr: <?php echo $meldeYear; ?>
			<br>
			<?php echo $fileMitarbeiterCount." Mitarbeiter in File VS ".$mitarbeiterCount." in PV21" ?>
			<br>
			<br>
			<?php
				$fileVerwendungSums = $fileMitarbeiterSums['verwendungSums'];
				$verwendungSums = $mitarbeiterSums['verwendungSums'];
				$fileLehreSums = $fileMitarbeiterSums['lehreSums'];
				$lehreSums = $mitarbeiterSums['lehreSums'];
				$fileFunktionSums = $fileMitarbeiterSums['funktionSums'];
				$funktionSums = $mitarbeiterSums['funktionSums'];

			?>
			<div class="row">
				<div class="col-6">
				<?php
					foreach ($messages as $key => $msgArr)
					{
						echo "<br><br>".count($msgArr)." Fehler für $key:";
						echo "<br>----------------------------------------------------------";
						foreach ($msgArr as $msgObj)
						{
							echo "<br>".(isEmptyString($msgObj->uid) ? '' : $msgObj->uid.": ").$msgObj->message;
						}
					}
				?>
				</div>
				<div class="col-6">
					<table class="table table-bordered table-sm table-responsive" id="verwendungSums">
						<thead>
							<tr>
								<td colspan="7" class="text-center">VERWENDUNG SUMMEN</td>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td></td>
								<td>ANZAHL File</td>
								<td>ANZAHL</td>
								<td>VZAE File</td>
								<td>VZAE</td>
								<td>JVZAE File</td>
								<td>JVZAE</td>
							</tr>
					<?php
						$totalSums = array_fill(0, 6, 0);
						foreach ($fileVerwendungSums as $key => $oldSum)
						{
							echo '<tr>';
							echo '<td>'.$key.' - '.$oldSum['name'].'</td>';
							echo '<td>'.$oldSum['count'].'</td>';
							echo '<td>'.$verwendungSums[$key]['count'].'</td>';
							echo '<td>'.$oldSum['vzae'].'</td>';
							echo '<td>'.$verwendungSums[$key]['vzae'].'</td>';
							echo '<td>'.$oldSum['jvzae'].'</td>';
							echo '<td>'.$verwendungSums[$key]['jvzae'].'</td>';
							echo '</tr>';

							$totalSums[0] += $oldSum['count'];
							$totalSums[1] += $verwendungSums[$key]['count'];
							$totalSums[2] += $oldSum['vzae'];
							$totalSums[3] += $verwendungSums[$key]['vzae'];
							$totalSums[4] += $oldSum['jvzae'];
							$totalSums[5] += $verwendungSums[$key]['jvzae'];
						}
					?>
						</tbody>
						<tfoot>
							<tr>
								<td>&nbsp;</td>
								<?php foreach ($totalSums as $sum): ?>
									<td><?php echo $sum ?></td>
								<?php endforeach; ?>
							</tr>
						</tfoot>
					</table>
					<table class="table table-bordered table-sm table-responsive">
						<thead>
							<tr>
								<td colspan="3" class="text-center">LEHRE SUMMEN</td>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td></td>
								<td>SUMME File</td>
								<td>SUMME</td>
							</tr>
					<?php
						$totalSums = array_fill(0, 2, 0);
						foreach ($fileLehreSums as $key => $oldSum)
						{
							echo '<tr>';
							echo '<td>'.$key.'</td>';
							echo '<td>'.$oldSum.'</td>';
							echo '<td>'.$lehreSums[$key].'</td>';
							echo '</tr>';

							$totalSums[0] += $oldSum;
							$totalSums[1] += $lehreSums[$key];
						}
					?>
						</tbody>
						<tfoot>
							<tr>
								<td>&nbsp;</td>
								<?php foreach ($totalSums as $sum): ?>
									<td><?php echo $sum ?></td>
								<?php endforeach; ?>
							</tr>
						</tfoot>
					</table>
					<table class="table table-bordered table-sm table-responsive">
						<thead>
							<tr>
								<td colspan="3" class="text-center">FUNKTION SUMMEN</td>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td></td>
								<td>ANZAHL File</td>
								<td>ANZAHL</td>
							</tr>
					<?php
						$totalSums = array_fill(0, 2, 0);
						foreach ($fileFunktionSums as $key => $oldSum)
						{
							$newFunktionSum = array_key_exists($key, $funktionSums) ? $funktionSums[$key]->count : 'unbekannt';
							echo '<tr>';
							echo '<td>'.$key.' - '.$oldSum->name.'</td>';
							echo '<td>'.$oldSum->count.'</td>';
							echo '<td>'.$newFunktionSum.'</td>';
							echo '</tr>';

							$totalSums[0] += $oldSum->count;
							$totalSums[1] += $newFunktionSum;
						}
					?>
						</tbody>
						<tfoot>
							<tr>
								<td>&nbsp;</td>
								<?php foreach ($totalSums as $sum): ?>
									<td><?php echo $sum ?></td>
								<?php endforeach; ?>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>
	</div>

<?php $this->load->view('templates/FHC-Footer', $includesArray); ?>

