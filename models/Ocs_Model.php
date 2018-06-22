<?php
/* Copyright (C) 2018 fhcomplete.org
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 * Authors: Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at>,
 */
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Ocs_Model extends FHC_Model
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->NextcloudConfig = $this->config->item('FHC-Core-Nextcloud');
	}

	/**
	 * Add a new group
	 * @param string $group Name of Group.
	 * @return boolean true if ok, false on error
	 */
	public function addGroup($group)
	{
		$ch = curl_init();

		$url = $this->NextcloudConfig['url'].'ocs/v1.php/cloud/groups';
		$data = 'groupid='.curl_escape($ch, $group);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7);
		curl_setopt($ch, CURLOPT_USERAGENT, "FH-Complete");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		if (!$this->NextcloudConfig['verifyssl'])
		{
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		$headers = array(
			'OCS-APIRequest: true',
			'Authorization: Basic '. base64_encode($this->NextcloudConfig['username'].":".$this->NextcloudConfig['password'])
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response = curl_exec($ch);

		if (curl_errno($ch))
		{
			show_error('Curl error: '.curl_error($ch));
			curl_close($ch);
			return false;
		}
		else
		{
			/* Success Response
			<ocs>
			<meta>
				<status>ok</status>
				<statuscode>100</statuscode>
				<message>OK</message>
				<totalitems></totalitems>
				<itemsperpage></itemsperpage>
			</meta>
			<data/>
			</ocs>
			*/
			/* Failure Response
			<ocs>
			<meta>
				<status>failure</status>
				<statuscode>102</statuscode>
				<message></message>
				<totalitems></totalitems>
				<itemsperpage></itemsperpage>
			</meta>
			<data/>
			</ocs>
			*/
			curl_close($ch);
			if ($this->_parseStatuscode($response) == '100')
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Adds a User to an existing group
	 * @param string $group Name of the Group.
	 * @param string $user Name of the User to add.
	 * @return boolean true if ok, false on error
	 */
	public function addUserToGroup($group, $user)
	{
		$ch = curl_init();

		$url = $this->NextcloudConfig['url'].'ocs/v1.php/cloud/users/'.curl_escape($ch, $user).'/groups';
		$data = 'groupid='.curl_escape($ch, $group);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7);
		curl_setopt($ch, CURLOPT_USERAGENT, "FH-Complete");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		if (!$this->NextcloudConfig['verifyssl'])
		{
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		$headers = array(
			'OCS-APIRequest: true',
			'Authorization: Basic '. base64_encode($this->NextcloudConfig['username'].":".$this->NextcloudConfig['password'])
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response = curl_exec($ch);

		if (curl_errno($ch))
		{
			show_error('Curl error: '.curl_error($ch));
			curl_close($ch);
			return false;
		}
		else
		{
			/* Success response
			<ocs>
			<meta>
				<status>ok</status>
				<statuscode>100</statuscode>
				<message>OK</message>
				<totalitems></totalitems>
				<itemsperpage></itemsperpage>
			</meta>
			<data/>
			</ocs>
			*/
			curl_close($ch);
			if ($this->_parseStatuscode($response) == '100')
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Get the Memebers of a group
	 * @param string $group Name of the group.
	 * @return array of Users
	 */
	public function getGroupMember($group)
	{
		$ch = curl_init();

		$url = $this->NextcloudConfig['url'].'ocs/v1.php/cloud/groups/'.curl_escape($ch, $group);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7);
		curl_setopt($ch, CURLOPT_USERAGENT, "FH-Complete");

		if (!$this->NextcloudConfig['verifyssl'])
		{
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		$headers = array(
			'OCS-APIRequest: true',
			'Authorization: Basic '. base64_encode($this->NextcloudConfig['username'].":".$this->NextcloudConfig['password'])
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response = curl_exec($ch);

		if (curl_errno($ch))
		{
			show_error('Curl error: '.curl_error($ch));
			curl_close($ch);
			return false;
		}
		else
		{
			/*
			<ocs>
				<meta>
					<status>ok</status>
					<statuscode>100</statuscode>
					<message>OK</message>
					<totalitems></totalitems>
					<itemsperpage></itemsperpage>
				</meta>
				<data>
			 		<users>
						<element>oesi</element>
					</users>
				</data>
			</ocs>
			*/
			curl_close($ch);
			if ($this->_parseStatuscode($response) == '100')
			{
				$dom = new DOMDocument();
				$dom->loadXML($response);
				$usersnode = $dom->getElementsByTagName('users');
				$userslist = $usersnode[0]->getElementsByTagName('element');
				$user_arr = array();
				foreach ($userslist as $row)
				{
					$user_arr[] = $row->textContent;
				}
				return $user_arr;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Get the Apps of the Nextcloud instance
	 * @return array of apps
	 */
	public function getApps()
	{
		$ch = curl_init();

		$url = $this->NextcloudConfig['url'].'ocs/v1.php/cloud/apps';

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7);
		curl_setopt($ch, CURLOPT_USERAGENT, "FH-Complete");

		if (!$this->NextcloudConfig['verifyssl'])
		{
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		$headers = array(
			'OCS-APIRequest: true',
			'Authorization: Basic '. base64_encode($this->NextcloudConfig['username'].":".$this->NextcloudConfig['password'])
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response = curl_exec($ch);

		if (curl_errno($ch))
		{
			show_error('Curl error: '.curl_error($ch));
			curl_close($ch);
			return false;
		}
		else
		{
			/*
			<ocs>
				<meta>
					<status>ok</status>
					<statuscode>100</statuscode>
				</meta>
				<data>
			 		<appss>
						<element>files</element>
					</apps>
				</data>
			</ocs>
			*/
			curl_close($ch);
			if ($this->_parseStatuscode($response) == '100')
			{
				$dom = new DOMDocument();
				$dom->loadXML($response);
				$usersnode = $dom->getElementsByTagName('apps');
				$appslist = $usersnode[0]->getElementsByTagName('element');
				$app_arr = array();
				foreach ($appslist as $row)
				{
					$app_arr[] = $row->textContent;
				}
				return $app_arr;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Remove a User from an existing group
	 * @param string $group Name of the Group.
	 * @param string $user Name of the User to remove.
	 * @return boolean true if ok, false on error
	 */
	public function removeUserFromGroup($group, $user)
	{
		$ch = curl_init();

		$url = $this->NextcloudConfig['url'].'ocs/v1.php/cloud/users/'.curl_escape($ch, $user).'/groups';
		$data = 'groupid='.curl_escape($ch, $group);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7);
		curl_setopt($ch, CURLOPT_USERAGENT, "FH-Complete");
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		if (!$this->NextcloudConfig['verifyssl'])
		{
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		$headers = array(
			'OCS-APIRequest: true',
			'Authorization: Basic '. base64_encode($this->NextcloudConfig['username'].":".$this->NextcloudConfig['password'])
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response = curl_exec($ch);

		if (curl_errno($ch))
		{
			show_error('Curl error: '.curl_error($ch));
			curl_close($ch);
			return false;
		}
		else
		{
			/* Success response
			<ocs>
			<meta>
				<status>ok</status>
				<statuscode>100</statuscode>
				<message>OK</message>
				<totalitems></totalitems>
				<itemsperpage></itemsperpage>
			</meta>
			<data/>
			</ocs>
			*/
			curl_close($ch);
			if ($this->_parseStatuscode($response) == '100')
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Parses the Statuscode of a XML
	 * @param string $xml XML Response.
	 * @return statuscode or false
	 */
	private function _parseStatuscode($xml)
	{
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$statuscodes = $dom->getElementsByTagName('statuscode');
		if (isset($statuscodes[0]))
			return $statuscodes[0]->textContent;
		else
			return false;
	}
}
