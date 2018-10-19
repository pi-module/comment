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
class Type extends Model
{
    /**
     * Columns to be encoded
     *
     * @var array
     */
    protected $encodeColumns = array(
        'params'  => true,
    );
}
