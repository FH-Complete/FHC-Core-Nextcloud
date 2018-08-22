<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Library for syncing lvs and lv-users with Nextcloud
 */
class NextcloudSyncLib
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Gets CI instance
		$this->ci =& get_instance();
		$this->ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$this->ci->load->model('extensions/FHC-Core-Nextcloud/Ocs_Model', 'OcsModel');
	}

	/**
	 * Adds (syncs) Lehrveranstaltung Groups in Nextcloud
	 * @param $studiensemester_kurzbz
	 * @param null $ausbildungssemester
	 * @param null $studiengang_kz
	 * @param null $lehrveranstaltung_ids
	 * @param bool $syncusers wether to add the users of lv after creating group
	 */
	public function addLehrveranstaltungGroups($studiensemester_kurzbz, $ausbildungssemester = null, $studiengang_kz = null, $lehrveranstaltung_ids = null, $syncusers = true)
	{
		$groupdata = $this->ci->LehrveranstaltungModel->getLehrveranstaltungGroupNames($studiensemester_kurzbz, $ausbildungssemester, $studiengang_kz, $lehrveranstaltung_ids);

		if (hasData($groupdata))
		{
			foreach ($groupdata->retval as $group)
			{
				$lehrveranstaltung_id = $group->lehrveranstaltung_id;
				$groupname = $group->lvgroupname;

				echo '<br/>';

				if ($this->ci->OcsModel->addGroup($groupname))
					echo 'ok, group '.$groupname.' created';
				else
					echo 'creation of group '.$groupname.' failed';

				//TODO check if failed because group already exists
				if (isset($syncusers) && $syncusers === true)
					$this->addUsersToLvGroup($studiensemester_kurzbz, $lehrveranstaltung_id);
			}
		}
		else
		{
			echo 'no lv groups found';
		}

		echo "<br />done";
	}

	/**
	 * Adds users (students + lecturers) to an existing group in Nextcloud. Group name is generated with same method as when adding groups.
	 * @param $studiensemester_kurzbz
	 * @param $lehrveranstaltung_id
	 */
	public function addUsersToLvGroup($studiensemester_kurzbz, $lehrveranstaltung_id)
	{
		$groupdata = $this->ci->LehrveranstaltungModel->getLehrveranstaltungGroupNames($studiensemester_kurzbz, null, null, $lehrveranstaltung_id);

		if (isError($groupdata))
			show_error($groupdata->retval);

		if (count($groupdata->retval) == 1)
			$groupname = $groupdata->retval[0]->lvgroupname;
		else
		{
			echo "wrong number of groups";
			return;
		}

		$lecturerdata = $this->ci->LehrveranstaltungModel->getLecturersByLv($studiensemester_kurzbz, $lehrveranstaltung_id);

		if (isError($lecturerdata))
			show_error($lecturerdata->retval);

		$studentdata = $this->ci->LehrveranstaltungModel->getStudentsByLv($studiensemester_kurzbz, $lehrveranstaltung_id);

		if (isError($studentdata))
			show_error($studentdata->retval);

		$lecturersadded = $studentsadded = 0;

		if (hasData($lecturerdata))
		{
			foreach ($lecturerdata->retval as $lecturer)
			{
				echo '<br/>';

				$uid = $lecturer->uid;

				if ($this->ci->OcsModel->addUserToGroup($groupname, $uid))
				{
					echo 'ok, lecturer with uid '.$uid.' added to group '.$groupname;
					$lecturersadded++;
				}
				else
					echo 'adding lecturer with uid '.$uid.' to group '.$groupname.' failed';
			}
		}
		else
		{
			echo 'no lecturers';
		}

		if (hasData($studentdata))
		{

			foreach ($studentdata->retval as $student)
			{
				echo '<br/>';

				$uid = $student->uid;

				if ($this->ci->OcsModel->addUserToGroup($groupname, $uid))
				{
					echo 'ok, student with uid '.$uid.' added to group '.$groupname;
					$studentsadded++;
				}
				else
					echo 'adding student with uid '.$uid.' to group '.$groupname.' failed';
			}
		}
		else
		{
			echo 'no students';
		}

		echo "<br />done, ".$studentsadded." students, ".$lecturersadded." lecturers added";
	}
}
