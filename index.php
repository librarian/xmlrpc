<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 

/**
 * MaxSite CMS
 * (c) http://max-3000.com/
 */


# функция автоподключения плагина
function xmlrpc_autoload()
{
    mso_hook_add( 'init', 'xmlrpc_srv_init');
    mso_hook_add( 'init', 'xmlrpc_cli_init');
}


# функция выполняется при активации (вкл) плагина
function xmlrpc_activate($args = array())
{	
}

# функции плагина
function xmlrpc_srv_init($arg = array())
{
    if (mso_segment(1) == 'xmlrpc') {
        $CI = &get_instance();
        $CI->load->library('xmlrpc');
        $CI->load->library('xmlrpcs');

        $config['functions']['blogger.getUsersBlogs']       = array('function' => 'xmlrpc_getUsersBlogs');
        $config['functions']['blogger.deletePost']          = array('function' => 'xmlrpc_deletePost');
        $config['functions']['metaWeblog.newPost']          = array('function' => 'xmlrpc_newPost');
        $config['functions']['metaWeblog.editPost']         = array('function' => 'xmlrpc_editPost');
        $config['functions']['metaWeblog.getPost']          = array('function' => 'xmlrpc_getPost');
        $config['functions']['metaWeblog.getCategories']    = array('function' => 'xmlrpc_getCategories');
        $config['functions']['metaWeblog.getRecentPosts']   = array('function' => 'xmlrpc_getRecentPosts');
        $config['functions']['metaWeblog.deletePost']       = array('function' => 'xmlrpc_deletePost');
        $config['functions']['metaWeblog.getUsersBlogs']    = array('function' => 'xmlrpc_getUsersBlogs');
        $config['functions']['metaWeblog.newMediaObject']   = array('function' => 'xmlrpc_newMediaObject');
        $config['object'] = $CI;
         
        $CI->xmlrpc->set_debug(TRUE);
        $CI->xmlrpcs->initialize($config);
        $CI->xmlrpcs->serve();
    }
}

function xmlrpc_cli_init($arg = array()) {
    
    if (mso_segment(1) == 'xmlrpc_test') {
        $CI = &get_instance();
        $CI->load->library('xmlrpc');
        $CI->xmlrpc->server('http://x.libc6.org/xmlrpc/', 80);
        $CI->xmlrpc->set_debug(TRUE);
        // getUsersBlogs
        $CI->xmlrpc->method('blogger.getUsersBlogs');
        $request = array (
                'xmlrpc_test',
                'login',
                'password',
                );
        // getPost
        $CI->xmlrpc->method('metaWeblog.getPost');
        $request = array (
                41,
                'login',
                'password',
                );
        // getCategories
        $CI->xmlrpc->method('metaWeblog.getCategories');
        $request = array (
                0,
                'login',
                'password',
                );
        // getRecentPosts
        $CI->xmlrpc->method('metaWeblog.getRecentPosts');
        $request = array (
                0,
                'login',
                'password',
                5,
                );
        $CI->xmlrpc->request($request);
        if ( ! $CI->xmlrpc->send_request())
        {
            _pr($CI->xmlrpc->display_error());
        }
        else
        {
            _pr($CI->xmlrpc->display_response());
        }
    }

}

function xmlrpc_filter_input($text = '') {

    // Выдрал из _mso_login
    $CI = & get_instance();

    $text = trim($text);
    $text = $CI->security->xss_clean($text, false);
    $text = strip_tags($text);
    $text = htmlspecialchars($text);

    return $text;
}

function xmlrpc_check_auth ($login = '', $password = '') {


    $CI = & get_instance();
    
    $login = xmlrpc_filter_input($login);
    $password = xmlrpc_filter_input($password);

    # проверяем на strip - запрещенные символы
    if ( ! mso_strip($login, true) or ! mso_strip($password, true) ) {
        return false;
    }
    
    $password = mso_md5($password);

    $CI->db->from('users');
    $CI->db->select('*');
    $CI->db->limit(1);
    
    $CI->db->where('users_login', $login);
    $CI->db->where('users_password', $password);
    $query = $CI->db->get();

    if ($query->num_rows() > 0) { 
        return true;
    } else {
        return false;
    }
}

// API Actions

// metaweblog.getUserBlogs request 0 - appkey, 1 - username, 2 - password
function xmlrpc_getUsersBlogs ($request) {
    error_log(__FUNCTION__  . "\n", 3, FCPATH . '/error.log');
    $CI = & get_instance();
    $par = $request->output_parameters();
    if(xmlrpc_check_auth($par[1], $par[2])) {
        $response = array
            (       
                'url' => array(getinfo('siteurl'),'string'),
                'blogid' => array(1,'integer'),
                'blogName' => array(mso_head_meta('title'),'string')
            );
        $response = array ($response,'struct');
        $response = array(array($response),'array');
        return $CI->xmlrpc->send_response($response);
    } else {
        return $CI->xmlrpc->send_error_message('100', 'Invalid Access');
    }
}
// metaweblog.getPost request 0 - postid, 1 - username, 2 - password
function xmlrpc_getPost ($request) {
    require_once( getinfo('common_dir') . 'page.php' );
    error_log(__FUNCTION__  . "\n", 3, FCPATH . '/error.log');
    $CI = & get_instance();
    $par = $request->output_parameters();
    $id = xmlrpc_filter_input($par[0]);
    if(xmlrpc_check_auth($par[1], $par[2])) {
        $par = array( 
            'cut' => false, 
            'cat_order' => 'category_name', 
            'cat_order_asc' => 'asc', 
            'type' => false,
            'page_id' => $id,
            'custom_type' => 'home'
        );
        $pages = mso_get_pages ($par, $pagination);
        //pr($pages);
        extract($pages[0]);
        $url = getinfo('siteurl') . 'page/' . $page_slug;
        $description = $page_content;
        $title = $page_title;
        $date = mso_date_convert(DATE_ISO8601, $page_date_publish);
        $response = array
            (       
                'link' => array($url,'string'),
                'permaLink' => array($url, 'string'),
                'title' => array($title, 'string'),
                'description' => array($description ,'string'),
                'dateCreated' => array($date,'string')
            );
        $response = array ($response,'struct');
        $response = array(array($response),'array');
        return $CI->xmlrpc->send_response($response);
    } else {
        return $CI->xmlrpc->send_error_message('100', 'Invalid Access');
    }
}
// metaweblog.getCategories request 0 - blog id, 1 - username, 2 - password
function xmlrpc_getCategories ($request) {
    require_once( getinfo('common_dir') . 'category.php' );
    error_log(__FUNCTION__  . "\n", 3, FCPATH . '/error.log');
    $CI = & get_instance();
    $par = $request->output_parameters();
    if(xmlrpc_check_auth($par[1], $par[2])) {
        $categories = mso_cat_array();
        foreach ($categories as $k => $v) {
            extract ($v);
            $response = array
                (
                    'description' => array ($category_desc,'string'),
                    'title' => array ($category_name,'string'),
                    'htmlUrl' => array (getinfo('siteurl') . 'category/'.$category_slug ,'string'),
                    'rssUrl' => array (getinfo('siteurl') . 'category/'.$category_slug . '/feed','string')
                );
            $responses[] = array ($response,'struct');
        }
        $responses = array ($responses,'struct');
        $responses = array(array($responses),'array');
        return $CI->xmlrpc->send_response($responses);
    } else {
        return $CI->xmlrpc->send_error_message('100', 'Invalid Access');
    }
}
// metaweblog. request 0 - blog id, 1 - username, 2 - password, 3 - count
function xmlrpc_getRecentPosts ($request) {
    require_once( getinfo('common_dir') . 'page.php' );
    error_log(__FUNCTION__  . "\n", 3, FCPATH . '/error.log');
    $CI = & get_instance();
    $par = $request->output_parameters();
    $limit = xmlrpc_filter_input($par[3]);
    if(xmlrpc_check_auth($par[1], $par[2])) {
        $par = array( 
            'cut' => false, 
            'cat_order' => 'category_name', 
            'cat_order_asc' => 'asc', 
            'type' => false,
            'limit' => $limit,
            'custom_type' => 'home'
        );
        $pages = mso_get_pages ($par, $pagination);
        foreach ($pages as $k => $v) {
            extract ($v);
            $url = getinfo('siteurl') . 'page/' . $page_slug;
            $description = $page_content;
            $title = $page_title;
            $date = mso_date_convert(DATE_ISO8601, $page_date_publish);
            $response = array
                (       
                    'link' => array($url,'string'),
                    'permaLink' => array($url, 'string'),
                    'title' => array($title, 'string'),
                    'description' => array($description ,'string'),
                    'dateCreated' => array($date,'string')
                );
            $responses[] = array ($response,'struct');
        }
        $responses = array ($responses,'struct');
        $responses = array(array($responses),'array');
        return $CI->xmlrpc->send_response($responses);
    } else {
        return $CI->xmlrpc->send_error_message('100', 'Invalid Access');
    }
}
// metaweblog.newPost request 0 - post id, 1 - username, 2 - password, 3 - content, 4 - publis
function xmlrpc_newPost ($request) {
    error_log(__FUNCTION__  . "\n", 3, FCPATH . '/error.log');
}
// metaweblog. request 0 - blog id, 1 - username, 2 - password, 3 - struct
function xmlrpc_newMediaObject ($request) {
    error_log(__FUNCTION__  . "\n", 3, FCPATH . '/error.log');
}
// metaweblog.deletePost request 0 - appkey, 1 - post id,  2 - username, 3 - password
function xmlrpc_deletePost ($request) {
    error_log(__FUNCTION__  . "\n", 3, FCPATH . '/error.log');
    $CI = & get_instance();
    $par = $request->output_parameters();
    if(xmlrpc_check_auth($par[1], $par[2])) {
        $response = array
            (       
            );
        $response = array ($response,'struct');
        $response = array(array($response),'array');
        return $CI->xmlrpc->send_response($response);
    } else {
        return $CI->xmlrpc->send_error_message('100', 'Invalid Access');
    }
}
// metaweblog.editPost request 0 - post id, 1 - username, 2 - password, 3 - content, 4 - publish
function xmlrpc_editPost ($request) {
    error_log(__FUNCTION__  . "\n", 3, FCPATH . '/error.log');
}
# end file
