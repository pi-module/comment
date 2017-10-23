<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

$config = array(
    'leading_limit' => array(
        'title'         => _t('Leading page comment limit'),
        'description'   => _t('Number of comments on leading page.'),
        'value'         => 5,
        'filter'        => 'int',
    ),

    'list_limit' => array(
        'title'         => _t('List page comment limit'),
        'description'   => _t('Number of comments on list page.'),
        'value'         => 20,
        'filter'        => 'int',
    ),

    'display_operation' => array(
        'title'         => _t('Display operation'),
        'description'   => _t('Level of operations on post list pages.'),
        'value'         => 'author',
        'edit'          => array(
            'type'      => 'select',
            'options'   => array(
                'value_options'     => array(
                    ''          => _t('Disable'),
                    'member'    => _t('For members'),
                    'author'    => _t('For authors'),
                    'admin'     => _t('For administrators'),
                ),
            ),
        ),
    ),

    'auto_approve'  => array(
        'title'         => _t('Auto approve submission'),
        'edit'          => 'checkbox',
        'value'         => 1,
        'filter'        => 'int',
    ),

    'guest_approve'  => array(
        'title'         => _t('Guest can send comment'),
        'edit'          => 'checkbox',
        'value'         => 0,
        'filter'        => 'int',
    ),

    'markup_format' => array(
        'title'         => _t('Content format'),
        'edit'          => array(
            'type'  => 'select',
            'options'   => array(
                'value_options' => array(
                    'text'       => _t('Plain text'),
                    'markdown'  => _t('Markdown'),
                    'html'      => _t('HTML'),
                    //'js'        => _t('JavaScript/HTML'),
                    //'custom'    => _t('Custom (must be defined in `Custom\Comment\Api\Markup\`)')
                ),
            ),
        ),
        'value'         => 'text',
    ),

    'markup_filters'    => array(
        'title'         => _t('Options for text format'),
        'edit'          => array(
            'type'  => 'multi_checkbox',
            'options'   => array(
                'value_options' => array(
                    'tag'       => _t('Enable tag link'),
                    'user'      => _t('Enable username link'),
                    'linkify'   => _t('Enable URL link'),
                ),
            ),
        ),
        'filter'        => 'array',
    ),
   'linkify_trust_domain'  => array(
        'title'         => _t('Trust domain'),
        'description'   => _t("Separe domains by a comma. Trust domains urls are dofollow, otherwise non trust domains urls are rel=nofollow. Mydomain.com filters any subdomain or directories (e.g. www.mydomain.com, shop.mydomain.com, ..., but also mydomain.com/user, etc.)"),
        'filter'        => 'text',
        'value'         => parse_url(Pi::url())['host']
    ),
    'time_to_edit_or_delete' => array(
        'title'         => _t('Time to edit/delete '),
        'description'   => _t('Time to edit/delete a comment, in second'),
        'value'         => 300,
        'filter'        => 'int',
    ),

     'datetime_format' => array(
        'title'         => _t('Date & Time format '),
        'description'   => _t('Indicate here the desired Date & Time format (PHP way - for instance d/m/Y H:i)'),
        'value'         => "d/m/Y H:i",
        'filter'        => 'text',
    ),
    'badge_admin' => array(
        'title'         => _t('URL for admin badge'),
        'value'         => "",
        'filter'        => 'text',
    ),
    'intro' => array(
        'title'         => _t('Intro for Index Page'),
        'value'         => _t("Text displayed on top of Index Page Comment List"),
        'filter'        => 'string',
        'edit'          => 'textarea',
        
    ),
   

     /*
    'user_domain'   => array(
        'title'         => _t('User domain'),
        'description'   => _t('URL that add the timeline to user system'),
        'value'         => '',
        'filter'        => 'string',
    ),
    */
);

return $config;