<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
defined('_JEXEC') or die('Restricted access');

/** To build create album simple schema
 *
 * @since  1.8.8
 */
class CreatealbumSimpleSchema
{
	public $id;

	public $cover_id;

	public $uid;

	public $type;

	public $user_id;

	public $title;

	public $caption;

	public $created;

	public $assigned_date;

	public $cover_featured;

	public $cover_large;

	public $cover_square;

	public $cover_thumbnail;
}
