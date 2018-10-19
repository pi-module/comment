<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace Module\Comment\Model;

use Pi\Application\Model\Model;

/**
 * Comment type model
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class Post extends Model
{
  
    const TYPE_REVIEW = 1;
    const TYPE_COMMENT = 2;
    const TYPE_ALL = 3;
    
    protected $mediaLinks = array('main_image', 'additional_images');

    /**
     * {@inheritDoc}
     */
    protected $rowClass = 'Module\Comment\Model\Post\RowGateway';
}
