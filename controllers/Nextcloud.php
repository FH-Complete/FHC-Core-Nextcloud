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

class Nextcloud extends Auth_Controller
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
			'index'=>'admin:rw'
			)
		);
		$this->config->load('extensions/FHC-Core-Nextcloud/config');
	}

	/**
	 * Index Controller
	 * @return void
	 */
	public function index()
	{
		$this->load->library('WidgetLib');
		$this->load->library('extensions/FHC-Core-Nextcloud/OcsLib', 'ocslib');

		/*
		if($users = $this->ocslib->getGroupMember($groupname))
		{
			echo "Group Members:";
			var_dump($users);
		}
		*/

		/*
		if($this->ocslib->addGroup($groupname))
			echo "ok";
		else
			echo "failed";
		*/

		/*
		if($this->ocslib->addUserToGroup($groupname, $username))
			echo "ok";
		else
			echo "failed";
		*/
		$this->load->view('extensions/FHC-Core-Nextcloud/Nextcloud');
	}
}
