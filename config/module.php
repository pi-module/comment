<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

/**
 * User module meta
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
return array(
    'meta'  => array(
        'title'         => _a('Comment'),
        'description'   => _a('Comment & Review management and services.'),
        'version'       => '1.3.9',
        'license'       => 'New BSD',
        'demo'          => 'http://demo.piengine.org',
        'icon'          => 'fa-comment'
    ),
    // Author information
    'author'    => array(
        // Author full name, required
        'Dev'       => 'Taiwen Jiang; Zongshu Lin; Marc Desrousseaux; Frederic Tissot; Mickael Stamm; Hossein Azizabadi',
        'UI/UE'     => '@zhangsimon, @loidco, @marc-pi',
        'QA'        => '@lavenderlin, @marc-pi',
        // Email address, optional
        'Email'     => 'taiwenjiang@tsinghua.org.cn',
        // Website link, optional
        'Website'   => 'http://piengine.org',
    ),

    // Resource
    'resource' => array(
        // Database meta
        'database'      => array(
            // SQL schema/data file
            'sqlfile'   => 'sql/mysql.sql',
        ),
        'config'        => 'config.php',
        'user'          => 'user.php',
        'block'         => 'block.php',
        'navigation'    => 'nav.php',
        'route'         => 'route.php',
        'comment'       => 'comment.php',
        'event'         => 'event.php',
    ),
);
