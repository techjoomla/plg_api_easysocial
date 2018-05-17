<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
defined('_JEXEC') or die('Restricted access');

/** To build simple conversation schema
 *
 * @since  1.8.8
 */
class converastionSimpleSchema
{
	public $conversion_id;

	public $created_date;

	public $lastreplied_date;

	public $isread;

	public $messages;

	public $lapsed;

	public $participant;

	public $lastMessage;

	public $lastMessageDate;

	public $newMsgCount;
}

/** To build simple message schema
 *
 * @since  1.8.8
 */
class MessageSimpleSchema {

	public $id;

	public $message;

	public $attachment;

	public $created_date;

	public $created_by;

	public $lapsed;

	public $isself;
}

/** To build simple reply schema
 *
 * @since  1.8.8
 */
class ReplySimpleSchema {

	public $id;

	public $created_by;

	public $reply;

	public $created_date;

	public $lapsed;
}
