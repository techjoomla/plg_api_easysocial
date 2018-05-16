<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
defined('_JEXEC') or die('Restricted access');

/** streamSimpleSchema
 *
 * @since  1.8.8
 */
class streamSimpleSchema
{
	public $id;

	public $title;

	public $type;

	public $group;

	public $element_id;

	public $preview;

	public $raw_content_url;

	public $content;

	public $actor;

	public $published;

	public $last_replied;

	public $likes;

	public $comment_element;

	public $comments;

	public $share_url;

	public $stream_url;

	public $with;

	public $isPinned;

	public $file_name;

	public $download_file_url;

	public $source;
	
	public $thumbnail;

	public $verb;

	public $isself;

	public $isAdmin;

	public $lapsed;

	public $mini;
}

/** likesSimpleSchema
 *
 * @since  1.8.8
 */
class likesSimpleSchema
{
	public $uid;

	public $type;

	public $stream_id;

	public $verb;

	public $created_by;

	public $total;

	public $element;

	public $group;

	public $hasLiked;

	public $like_obj;

	public $like_obj;
}

/** commentsSimpleSchema
 *
 * @since  1.8.8
 */
class commentsSimpleSchema
{
	public $uid;

	public $element;

	public $element_id;

	public $comment;

	public $verb;

	public $group;

	public $stream_id;

	public $created_by;

	public $created;

	public $lapsed;

	public $params;

	public $type;

	public $likes;

	public $likes;
}
