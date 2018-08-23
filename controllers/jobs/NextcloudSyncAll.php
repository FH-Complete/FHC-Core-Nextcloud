<?php

if (! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Cronjob for syncing all lvs and their users with Nextcloud for one semester
 *
 */

class NextcloudSyncAll extends FHC_Controller
{

	/**
	 * constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if ($this->input->is_cli_request())
		{
			$cli = true;
		}
		else
		{
			$this->output->set_status_header(403, 'Jobs must be run from the CLI');
			echo "Jobs must be run from the CLI";
			exit;
		}
	}

	/**
	 * Main function index as help
	 *
	 * @return	void
	 */
	public function index()
	{
		$result = "The following are the available command line interface commands\n\n";
		$result .= "php index.ci.php extensions/FHC-Core-Nextcloud/jobs/NextcloudSyncAll RunAll";

		echo $result.PHP_EOL;
	}

	/**
	 * @param null $studiensemester_kurzbz
	 * @param bool $syncusers
	 */
	public function runAll($studiensemester_kurzbz = null, $syncusers = true)
	{
		$this->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');

		$this->load->library('extensions/FHC-Core-Nextcloud/NextcloudSyncLib');

		//TODO regex for studsem?
		if (!isset($studiensemester_kurzbz))
		{
			$currstudiensemesterdata = $this->StudiensemesterModel->getAktOrNextSemester();

			if (!hasData($currstudiensemesterdata))
				show_error('no studiensemester retrieved');

			$studiensemester_kurzbz = $currstudiensemesterdata->retval[0]->studiensemester_kurzbz;
		}

		$this->nextcloudsynclib->addLehrveranstaltungGroups($studiensemester_kurzbz, null, null, null, $syncusers);
	}
}
