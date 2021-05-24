<?php
/******************************************************************************\
**   JoomGallery Plugin 'JoomJCom'                                            **
**   By: JoomGallery::ProjectTeam                                             **
**   Copyright (C) 2009 - 2012  Patrick Alt                                   **
**   Copyright (C) 2019 - 2021  JoomGallery::ProjectTeam                      **
**   Released under GNU GPL Public License                                    **
**   License: http://www.gnu.org/copyleft/gpl.html or have a look             **
**   at administrator/components/com_joomgallery/LICENSE.TXT                  **
\******************************************************************************/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/**
 * JoomGallery Plugin
 * Displays the number of comments of images and categories
 *
 * @package     Joomla
 * @subpackage  JoomGallery
 * @since       1.5
 */
class plgJoomgalleryJoomJCom extends JPlugin
{
  /**
   * Constructor
   *
   * For php4 compatability we must not use the __constructor as a constructor for plugins
   * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
   * This causes problems with cross-referencing necessary for the observer design pattern.
   *
   * @param   object  $subject  The object to observe
   * @param   object  $params   The object that holds the plugin parameters
   * @return  void
   * @since   1.5
   */
  public function __construct(&$subject, $params)
  {
    parent::__construct($subject, $params);

    $comments = JPATH_ROOT.'/components/com_jcomments/jcomments.php';
    if (file_exists($comments))
    {
      require_once($comments);
    }
    else
    {
      throw new Exception('JComments is not installed');
    }
  }

  /**
   * Displays the number of comments of a specific image
   *
   * Method is called by the view
   *
   * @param   int     $id The image ID
   * @return  string  The HTML output for displaying the number of comments
   * @since   1.5
   */
  public function onJoomAfterDisplayThumb($id)
  {
    if(!$this->params->get('image'))
    {
      return '';
    }

    $html = '
        <li>
          '.JText::sprintf('COM_JOOMGALLERY_COMMON_COMMENTS_VAR', JComments::getCommentsCount($id, 'com_joomgallery')).'
        </li>';

    return $html;
  }

  /**
   * Displays the number of comments of a specific category
   *
   * Method is called by the view
   *
   * @param   int     $id The category ID
   * @return  The HTML output for displaying the number of comments
   * @since   1.5
   */
  public function onJoomAfterDisplayCatThumb($id)
  {
    if(!$this->params->get('category'))
    {
      return '';
    }

    $html = '
        <li>
          '.JText::sprintf('COM_JOOMGALLERY_COMMON_COMMENTS_VAR', JComments::getCommentsCount($id, 'com_joomgallery_categories')).'
        </li>';

    return $html;
  }

  /**
   * Loads the data objects which JoomGallery will use in the
   * Toplist 'Last Commented' View.
   *
   * Method is called by the view
   *
   * @param   int  $rows   The variable in which the objects will be stored
   * @param   int  $limit  The number of images to load, usually the one configured in JoomGallery for the toplists view
   * @return  void
   * @since   1.5
   */
  public function onJoomGetLastComments(&$rows, $limit = null)
  {
    $user = JFactory::getUser();
    $db   = JFactory::getDBO();

    $query = $db->getQuery(true)
          ->select('jc.*,ca.*, a.owner AS owner, jc.date AS datetime, a.*')
          ->select(JoomHelper::getSQLRatingClause('a').' AS rating')
          ->from(_JOOM_TABLE_IMAGES.' AS a')
          ->from(_JOOM_TABLE_CATEGORIES.' AS ca')
          ->from('#__jcomments AS jc')
          ->where('a.id = jc.object_id')
          ->where('jc.object_group = '.$db->q('com_joomgallery'))
          ->where('a.catid = ca.cid')
          ->where('a.published = 1')
          ->where('a.approved = 1')
          ->where('a.access IN ('.implode(',', $user->getAuthorisedViewLevels()).')')
          ->where('jc.published = 1')
          ->where('ca.cid IN ('.implode(',', array_keys(JoomAmbit::getInstance()->getCategoryStructure())).')')
          ->where('ca.published = 1')
          ->where('ca.access IN ('.implode(',', $user->getAuthorisedViewLevels()).')')
          ->order('jc.date DESC');
    $db->setQuery($query, 0, (int) $limit);

    if(!$rows = $db->loadObjectList())
    {
      return;
    }

    // Prepare the comments for displaying them and count the comments for each image
    require_once JCOMMENTS_HELPERS.'/plugin.php';
    foreach($rows as $key => $row)
    {
      JComments::prepareComment($rows[$key]);
      $rows[$key]->comments = JComments::getCommentsCount($row->object_id, 'com_joomgallery');
    }

    // JoomGallery wants to know that this data is delivered by a plugin
    $rows[0]->delivered_by_plugin = true;
  }
}