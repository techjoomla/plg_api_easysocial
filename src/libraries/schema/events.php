<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
defined('_JEXEC') or die('Restricted access');

/** To build events simple schema
 *
 * @since  1.8.8
 */
class EventsSimpleSchema
{
	public $id;

	public $title;

	public $description;

	public $params;

	public $details;

	public $guests;

	public $featured;

	public $created;

	public $categoryId;

	public $start_date;

	public $end_date;

	public $start_date_unix;

	public $end_date_unix;

	public $category_name;

	public $isAttending;

	public $isOwner;

	public $isMaybe;

	public $location;

	public $longitude;

	public $latitude;

	public $cover_image;

	public $start_date_ios;

	public $end_date_ios;

	public $isoverevent;

	public $share_url;

	public $isPendingMember;

	public $isRecurring;                                

	public $hasRecurring;

	public $cover_position;

	public $end_time;

	public $type;

	public $event_type;

	public $isMember;

	public $owner;

	public $owner_id;

	public $total_guest;

	public $event_map_url_andr;

	public $event_map_url_ios;

	public $isInvited;

	public $action;
}
