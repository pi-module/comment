<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

/**
 * Routes
 */

return array(
    // route name
    'comment'  => array(
        'name'      => 'comment',
        'type'      => 'Module\Comment\Route\Comment',
        'options'   => array(
            'prefix'    => '/comment',
            'defaults'  => array(
                'module'        => 'comment',
                'controller'    => 'index',
                'action'        => 'index'
            )
        ),
    )
);

