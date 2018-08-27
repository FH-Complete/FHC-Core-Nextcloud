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

		$this->config->load('extensions/FHC-Core-Nextcloud/config');
		$this->load->library('extensions/FHC-Core-Nextcloud/NextcloudSyncLib');
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
	 * Runs sync for all lvs (in current/next studiensemester) and oes (active)
	 */
	public function runAll()
	{
		// Sync lv groups
		$this->runLvGroups();

		// Sync oe groups
		$this->runOeGroups();
	}

	/**
	 * Initializes sync for all lvs of all Studiengaenge for a given Studiensemester
	 * @param null $studiensemester_kurzbz if not given, actual or next (in summer) semester is retrieved
	 * @param bool $syncusers wether to sync students and lecturers of the group
	 */
	public function runLvGroups($studiensemester_kurzbz = null, $syncusers = true)
	{
		$studiensemester_kurzbz = $this->_getAktOrNextSemester($studiensemester_kurzbz);

		$this->nextcloudsynclib->addLehrveranstaltungGroups($studiensemester_kurzbz, null, null, null, $syncusers);
	}

	/**
	 * Initializes Oe group sync
	 */
	public function runOeGroups()
	{
		$this->nextcloudsynclib->addOeGroups();
	}

	/**
	 * Initializes deletion for all lvs of all Studiengaenge for a given Studiensemester
	 * @param null $studiensemester_kurzbz
	 */
	public function deleteLvGroups($studiensemester_kurzbz = null)
	{
		$studiensemester_kurzbz = $this->_getAktOrNextSemester($studiensemester_kurzbz);

		$this->nextcloudsynclib->deleteLehrveranstaltungGroups($studiensemester_kurzbz);
	}

	/**
	 * Retrieves current or next (in summer) Studiensemester if not provided
	 * @param $studiensemester_kurzbz
	 * @return the studiensemester_kurzbz
	 */
	private function _getAktOrNextSemester($studiensemester_kurzbz)
	{
		$this->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');

		if (!isset($studiensemester_kurzbz) || !preg_match("/^[W|S]S\d{4,}$/", $studiensemester_kurzbz))
		{
			$currstudiensemesterdata = $this->StudiensemesterModel->getAktOrNextSemester();

			if (!hasData($currstudiensemesterdata))
				show_error('no studiensemester retrieved');

			return $currstudiensemesterdata->retval[0]->studiensemester_kurzbz;
		}
		return $studiensemester_kurzbz;
	}
}
