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

		if ($this->ci->input->is_cli_request())
		{
			$this->nl = PHP_EOL;
		}
		else
		{
			$this->nl = '<br />';
		}
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

		echo 'NEXTCLOUD SYNC';
		echo $this->nl.str_repeat('-', 130);

		if (hasData($groupdata))
		{
			foreach ($groupdata->retval as $group)
			{
				$lehrveranstaltung_id = $group->lehrveranstaltung_id;
				$groupname = $group->lvgroupname;

				echo $this->nl.$this->nl;

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
			echo $this->nl.'no lv groups found';
		}

		echo $this->nl.str_repeat('-', 130);
		echo $this->nl.'NEXTCLOUD SYNC END';
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
			echo $this->nl.'wrong number of lv groups';
			return;
		}

		$lecturerdata = $this->ci->LehrveranstaltungModel->getLecturersByLv($studiensemester_kurzbz, $lehrveranstaltung_id);

		if (isError($lecturerdata))
			show_error($lecturerdata->retval);

		$studentdata = $this->ci->LehrveranstaltungModel->getStudentsByLv($studiensemester_kurzbz, $lehrveranstaltung_id);

		if (isError($studentdata))
			show_error($studentdata->retval);

		$nextcloudusers = $this->ci->OcsModel->getGroupMember($groupname);

		$lecturersadded = $studentsadded = $usersremoved = 0;
		if (is_array($nextcloudusers))
		{
			$lecturersno = count($lecturerdata->retval);

			$userstoadd = array_merge($lecturerdata->retval, $studentdata->retval);
			$uid_arr = array();

			for ($i = 0; $i < count($userstoadd); $i++)
			{
				echo $this->nl;

				$uid = $userstoadd[$i]->uid;
				$uid_arr[] = $uid;
				$lecturer = $i < $lecturersno;
				$usertype = $lecturer ? 'lecturer' : 'student';

				if (in_array($uid, $nextcloudusers))
					echo $usertype.' with uid '.$uid.' already exists in group '.$groupname;
				else
				{
					if ($this->ci->OcsModel->addUserToGroup($groupname, $uid))
					{
						echo 'ok, '.$usertype.' with uid '.$uid.' added to group '.$groupname;
						if ($lecturer)
							$lecturersadded++;
						else
							$studentsadded++;
					}
					else
						echo 'adding '.$usertype.' with uid '.$uid.' to group '.$groupname.' failed';
				}
			}

			$userstoremove = array_diff($nextcloudusers, $uid_arr);

			foreach ($userstoremove as $user)
			{
				echo $this->nl.'user in Nextcloud lvgroup but not in FAS lvgroup - removing '.$user.' from '.$groupname;

				echo $this->nl;
				if ($this->ci->OcsModel->removeUserFromGroup($groupname, $user))
				{
					echo 'user removed from group!';
					$usersremoved++;
				}
				else
				{
					echo 'user removal failed!';
				}
			}
		}
		else
			echo $this->nl.'Nextcloudusers could not be retrieved!';

		echo $this->nl.' '.$groupname.' done, '.$studentsadded.' students, '.$lecturersadded.' lecturers added, '.$usersremoved.' users removed';
	}
}
