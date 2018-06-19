<?php
	$this->load->view(
		'templates/FHC-Header',
		array(
			'title' => 'Nextcloud',
			'jquery' => true,
			'jqueryui' => true,
			'bootstrap' => true,
			'fontawesome' => true,
			'sbadmintemplate' => true,
			'ajaxlib' => true,
			'navigationwidget' => true
		)
	);
?>

<body>
	<div id="wrapper">

		<?php echo $this->widgetlib->widget('NavigationWidget'); ?>

		<div id="page-wrapper">
			<div class="container-fluid">
				<div class="row">
					<div class="col-lg-12">
						<h3 class="page-header">Nextcloud</h3>
					</div>
				</div>
				<div>
					Nextcloud Status Page
				</div>
			</div>
		</div>
	</div>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>

