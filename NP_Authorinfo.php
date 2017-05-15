<?php
/**
 * NP_Authorinfo.php
 * 
 * This program is Nucleus Plugin that display author infomation.
 * ===========================================================================
 * This program is free software and open source software; you can redistribute
 * it and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA  or visit
 * http://www.gnu.org/licenses/gpl.html
 * ===========================================================================
 * 
 * @author  takab<http://d.hatena.ne.jp/nucsl/>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @since   2005/12/10
 * @version CSV: $Id: NP_Authorinfo.php,v 1.2 2006/05/23 01:32:28 takab Exp $
 */

// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')) {
    function sql_table($name) {
        return 'nucleus_'.$name;
    }
}

/**
 * NP_Authorinfo
 * 
 * Nucleus Plugin that display author infomation.
 * 
 * @package plugin
 * @version 1.0.1
 */
class NP_Authorinfo extends NucleusPlugin {
    /**
     * @var array member infomation cache
     */
    var $_cache;
    
    /**
     * @var string directory name
     */
    var $_dirName = 'authorinfo';


    /**
     * Get member
     * 
     * If cache exists return that otherwise get member instance 
     * from display name
     * 
     * @param string $diplayname displayname of member
     * @return object MEMBER instance
     * @access public
     * @since 1.0.0 - 2006/05/22
     */
    function & getMember($displayname)
    {
        if (!isset($this->_cache[$displayname])) {
            $this->_cache[$displayname] =& MEMBER::createFromName($displayname);
        }
        return $this->_cache[$displayname]; 
    }

    /**
     * Get author image
     * 
     * return author image if exists
     * 
     * @param string $diplayname displayname of member
     * @return string url of author image
     * @access public
     * @since 1.0.0 - 2006/05/22
     */
    function getAuthorImageByName($diplayname)
    {
        global $DIR_MEDIA, $CONF;
        $filename = $DIR_MEDIA . $this->getDirName() . '/' 
            . basename($diplayname) . '.jpg';
        return @file_exists($filename)
            ? $CONF['MediaURL'] . $this->getDirName() . '/' 
                . basename($diplayname) . '.jpg?' . time()
            : $this->getAdminURL() . '_noimage.jpg';
    }
    
    /**
     * Get directory name
     * 
     * return directory name for author images  
     * 
     * @return string directory name for author images
     * @access public
     * @since 1.0.0 - 2006/05/22
     */
    function getDirName()
    {
        return basename($this->_dirName);
    }



    // Plugin event
    function event_QuickMenu(& $data) {
        global $member;

        // only show to admins
        if (!$member->isLoggedIn()) {
            return;
        }
        
        if ($member->isAdmin() || $member->getAdminBlogs()) {
            array_push($data['options'], array (
                'title' => 'AuthorInfo', 
                'url' => $this->getAdminURL() . 'index.php', 
                'tooltip' => 'Manage author infomation')
            );
        }

    }
    function event_PostPluginOptionsUpdate ($data) {
        global $DIR_MEDIA;
        if ('yes' == $this->getOption ('remove_image')) {
            $dir = $DIR_MEDIA . $this->getDirName() . '/';
            if ($handle = opendir ($dir)) {
                while ($entry = readdir ($handle)) {
                    $entry = basename($entry);
                    if (!is_dir ($dir . $entry) && @file_exists($dir . $entry)) {
                        unlink ($dir . $entry);
                    }
                }
                closedir ($handle);
            }
            $this->setOption ('remove_image', 'no');
        }
    }



    function doSkinVar($skinType, $type) {
//        global $blog, $manager, $CONF, $catid, $itemid;
    }
    
    function doTemplateVar(&$item, $key)
    {
        $key = trim($key);
        $mem =& $this->getMember($item->author);
        switch($key){
        case 'id':
            echo $mem->getID();
            break;
        case 'name':
            echo $mem->getDisplayName();
            break;
        case 'realname':
            echo $mem->getRealName();
            break;
        case 'notes':
            echo $mem->getNotes();
            break;
        case 'url':
            echo $mem->getURL();
            break;
        case 'email':
            echo $mem->getEmail();
            break;
        case 'image':
        case 'image_src':
            echo $this->getAuthorImageByName($mem->getDisplayName());
            break;
        case 'image':
        case 'image_tag':
            echo '<img src="'
                . $this->getAuthorImageByName($mem->getDisplayName())
                . '" alt="" />';
            break;
        }
    }


    // Plugin infomation
    
    function getName() {
        return 'Authorinfo';
    }
    function getAuthor() {
        return 'takab';
    }
    function getURL() {
        return 'http://d.hatena.ne.jp/nucsl/20060522/1148265392';
    }
    function getVersion() {
        return '1.0.1';
    }
    function getDescription() {
        return 'Show infomation of author in Template';
    }
    function supportsFeature($what) {
        switch ($what) {
            case 'SqlTablePrefix' :
                return 1;
            default :
                return 0;
        }
    }

    function hasAdminArea() {
        return 1;
    }

    function getEventList() {
        return array ('QuickMenu', 'PreItem', 'PostPluginOptionsUpdate');
    }
    
    function install () {
        global $DIR_MEDIA;
        $dir = $DIR_MEDIA . $this->getDirName();
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $this->createOption('max_size', 'Maximum size of author image in bytes.', 'text', '100000');
        $this->createOption('remove_image', 'Remove all autor image?', 'yesno', 'no');
        
    }
    
    function init()
    {
        global $DIR_MEDIA;
        $dir = $DIR_MEDIA . $this->getDirName();
        if (!is_dir($dir)) {
            mkdir($dir);
        }
    }
    
}
?>