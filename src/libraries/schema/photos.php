<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
defined('_JEXEC') or die('Restricted access');

/** To build photos simple schema
 *
 * @since  1.8.8
 */

class PhotosSimpleSchema
{
	public $id;

	public $album_id;

	public $user_id;

	public $uid;

	public $type;

	public $title;

	public $isowner;

	public $caption;

	public $created;

	public $state;

	public $assigned_date;

	public $image_large;

	public $image_square;

	public $image_thumbnail;

	public $image_featured;

	public $likes;

	public $comments;

	public $comment_element;
}
