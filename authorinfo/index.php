<?php
/**
 * authorinfo/index.php
 * 
 * display admin area and execute admin actions of NP_Authoinfo
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
 * @author  takab
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @since   2005/12/10
 * @version CSV: $Id: index.php,v 1.3 2006/05/23 01:32:28 takab Exp $
 */


$basedir = dirname(dirname(dirname(dirname(__FILE__))));
require "$basedir/config.php";
require_once "{$DIR_LIBS}PLUGINADMIN.php";

/**
 * PluginAdminAuthorinfo class
 * 
 * display admin area and execute admin actions of NP_Authoinfo
 * 
 * @package plugin
 * @version 1.0.1
 */
class PluginAdminAuthorinfo extends PluginAdmin{
    /**
     * @var integer 
     * 
     * Auth level for admin
     */
    var $auth = 0;
    
    /**
     * Constructor
     * 
     * @access public
     * @since 1.0.0 - 2006/05/22
     */
    function __construct($pluginName)
    {
        global $CONF, $blogid, $member,$manager;
          parent::__construct($pluginName);
        
        // Auth check
        if (!$member->isLoggedIn()) {
            $this->auth = 0;
        } else if ($member->isAdmin()) {
            // Super Admin
            $this->auth = 4;
        } else if ($blogs = $member->getAdminBlogs()) {
            // Blog Admin
            $this->auth = 2;
        } else {
            // Just A User
            $this->auth = 1;
        }

        // invalid access
        if (!$this->auth) {
            startUpError(_ERROR_DISALLOWED);
            exit;
        }
        
        // auth error
        if ($this->auth < 2) {
            $this->start();
            echo '<p>'._ERROR_DISALLOWED.'</p>';
            $this->end();
            exit;
        }
    }
    
    /**
     * Start display
     * 
     * Send HTTP headers and display html header 
     * for admin area and admin actions.
     * 
     * @access private
     * @since 1.0.0 - 2006/05/22
     */
    function start($extraHead = '')
    {
        global $CONF, $blogid, $manager;
        $adminurl = $this->plugin->getAdminURL();
        $deleteurl = hsc($manager->addTicketToUrl($adminurl . '?action=deletedone'));
        $extraHead = "<script type=\"text/javascript\" src=\"${adminurl}prototype.js\"></script>\n";
        $extraHead .= "<script type=\"text/javascript\" src=\"${adminurl}np_authorinfo.js\"></script>\n";
        $extraHead .= "<script type=\"text/javascript\">\n";
        $extraHead .= "  var Action_Url='$adminurl';\n";
        $extraHead .= "  var Noimage_Url='${adminurl}_noimage.jpg';\n";
        $extraHead .= "  var Delete_Url='$deleteurl';\n";
        $extraHead .= "</script>\n";
        sendContentType('application/xhtml+xml', 'admin-authorinfo', _CHARSET);
        parent::start($extraHead);
    }
    
    /**
     * End display
     * 
     * Display html footer
     * 
     * @access private
     * @since 1.0.0 - 2006/05/22
     */
    function end()
    {
        parent::end();
    }

    /**
     * Execute action
     * 
     * Dispatch admin action
     * 
     * @access public
     * @since 1.0.0 - 2006/05/22
     */
    function doAction()
    {
        $action = strtolower(requestVar('action'));
        if (!$action) {
            $action = 'default';
        }
        if (preg_match('/^[0-9a-z\-_]+$/', $action)) {
            $method = '_action' . ucfirst($action);
            if (method_exists($this, $method)) {
                $page = $this->$method();
            }
        }
    }
    
    /**
     * Default action
     * 
     * Display top page of admin area
     * 
     * @access private
     * @since 1.0.0 - 2006/05/22
     */
    function _actionDefault()
    {
        global $member;
        $this->start();
        
        $memberList = array();
        if ($this->auth == 4) {
            $query = 'SELECT mnumber,mname,mrealname FROM '.sql_table('member');
            $res = sql_query($query);
            while ($arr = sql_fetch_array($res)) {
                if (!empty($arr['mnumber'])) {
                    $memberList[] = $arr;
                }
            }
        } else if ($this->auth > 1) {
            $memberList[] = array(
                'mnumber' => $member->getID(),
                'mname' => $member->getDisplayName(),
                'mrealname' => $member->getRealName(),
            );
        }
?>
    <h2>メンバー画像</h2>
    <table style="width: 400px;">
        <tr><th>本名</th><th>画像</th><th>アクション</th></tr>
<?php
        foreach ($memberList as $curMember) {
?>
        <tr>
            <td><?php echo hsc($curMember['mrealname'])?></td>
            <td><img id="authorimg<?php echo intval($curMember['mnumber'])?>" src="<?php echo hsc($this->plugin->getAuthorImageByName($curMember['mname']))?>" alt="" width="64"/></td>
            <td>
                <input type="button" value="アップロード" onclick="np_authorinfo_uploadimage(event, <?php echo intval($curMember['mnumber'])?>);" /><br />
                <input type="button" value="削除" onclick="np_authorinfo_deleteimage(event, <?php echo intval($curMember['mnumber'])?>);"/>
            </td>
        </tr>
<?php
        }
?>
    
    </table>
<?php

        $this->end();
    }
    
    /**
     * Uploadform action
     * 
     * Display upload form
     * 
     * @access private
     * @since 1.0.0 - 2006/05/22
     */
    function _actionUploadform()
    {
        global $manager;
        $actionurl = $this->plugin->getAdminUrl();
        $mnumber = intRequestVar('mnumber');
        $mem =& MEMBER::createFromID($mnumber);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <style type="text/css">
  *{margin:0;padding:0;}
  h1{margin: 5px 0; font-size: 100%; color: #596d9d}
  </style>
</head>
<body>
<h1>
『<?php echo hsc($mem->getRealName())?>』さんの画像を
アップロードします
</h1>
<form action="./" method="post" enctype="multipart/form-data">
<div>
<input type="file" name="np_authorinfo_image" />
<input type="hidden" name="mnumber" value="<?php echo $mnumber?>"/>
<input type="hidden" name="action" value="uploaddone"/>
<?php $manager->addTicketHidden();?>

<input type="submit" /><br/>
<input type="button" value="閉じる" onclick="if(parent){ parent.np_authorinfo_uploadclose();}" />

</div>
</form>
</body>
</html>
<?php
    }
    
    /**
     * Uploaddone action
     * 
     * Check and move a upload file from uploadform
     * 
     * @access private
     * @since 1.0.0 - 2006/05/22
     */
    function _actionUploaddone()
    {
        global $manager, $DIR_MEDIA, $member;
        $mnumber = intPostVar('mnumber');
        if ($this->auth < 2) {
            return;
        } else if (($this->auth < 4) && $member->getID() != $mnumber) {
            return;
        }
        if ($manager->checkTicket() && MEMBER::existsID($mnumber)) {
            $targetmember =& MEMBER::createFromID(intPostVar('mnumber'));
            
            $uploadfile = $DIR_MEDIA . 'authorinfo/' 
                . basename($targetmember->getDisplayName()) . '.jpg';

            switch($_FILES['np_authorinfo_image']['error']) {
            case 1:
                $error = 'アップロードされたファイルは、php.ini の upload_max_filesize ディレクティブの値を超えています。';
                break;
            case 2:
                $error = 'アップロードされたファイルは、HTML フォームで指定された MAX_FILE_SIZE を超えています。';
                break;
            case 3:
                $error = 'アップロードされたファイルは一部のみしかアップロードされていません。';
                break;
            case 4:
                $error = 'ファイルはアップロードされませんでした。';
                break;
            case 5:
                $error = 'アップロードされたファイルは、php.ini の upload_max_filesize ディレクティブの値を超えています。';
                break;
            case 6:
                $error = 'テンポラリフォルダがありません。';
                break;
            case 7:
                $error = 'ディスクへの書き込みに失敗しました。';
                break;
            case 0:
            default:
                $error = '';
                break;
            }
            if (!$error && !$_FILES['np_authorinfo_image']['size']) {
                $error = 'ファイルが選択されていません。';
            } 

            if (!$error && !in_array($_FILES['np_authorinfo_image']['type'], array('image/jpeg', 'image/pjpeg'))) {
                $error = 'Jpeg画像ではありません。';
            } 
            
            if (!$error && ($_FILES['np_authorinfo_image']['size'] > $this->plugin->getOption('max_size'))) {
                $error = 'ファイルのサイズが大きすぎます。';
            } 
            
            if (!$error) {
                if ( is_uploaded_file($_FILES['np_authorinfo_image']['tmp_name'])) {
                    if (!move_uploaded_file($_FILES['np_authorinfo_image']['tmp_name'], $uploadfile)) {
                        $error = 'アップロードされたファイルの移動に失敗しました。';
                    }
                } else {
                   $error = '一時ファイルがアップロードファイルではありません。';
                }
            }

            
            sendContentType('application/xhtml+xml', 'admin-authorinfo', _CHARSET);
            if ($error) {
?>                
<script type="text/javascript">
  alert('<?php echo hsc($error)?>');
  if (parent) {
    parent.np_authorinfo_uploadfailure();
  }
</script>
<?php                
            } else {
                $imageurl = $this->plugin->getAuthorImageByName(
                    $targetmember->getDisplayName()
                );
?>                
<script type="text/javascript">
  alert('アップロードに成功しました');
  if (parent) {
    parent.np_authorinfo_uploadsuccess(
      <?php echo $mnumber?>, 
      '<?php echo hsc($imageurl)?>'
    );
  }
</script>
<?php                
            }

        } else {
?>                
<script type="text/javascript">
  alert('Invalid access!');
  if (parent) {
    parent.np_authorinfo_uploadfailure();
  }
</script>
<?php                
        }
    }

    /**
     * Delete action
     * 
     * Delete a member image and send result with JSON
     * (sending HTTP Header X-JSON).
     * 
     * @access private
     * @since 1.0.0 - 2006/05/22
     */
    function _actionDeletedone() 
    {
        global $manager, $DIR_MEDIA, $member;
        $mnumber = intGetVar('mnumber');
        if ($this->auth < 2) {
            $res = false;
        } else if (($this->auth < 4) && $member->getID() != $mnumber) {
            $res = false;
        } else if ($manager->checkTicket() && MEMBER::existsID($mnumber)) {
            $targetmember =& MEMBER::createFromID($mnumber);
            
            $uploadfile = $DIR_MEDIA . $this->plugin->getDirName() . '/' 
                . basename($targetmember->getDisplayName()) . '.jpg';
            
            
            $res = @unlink($uploadfile);
        } else {
            $res = false;
        }
        $adminurl = $this->plugin->getAdminURL();
        $deleteurl = $manager->addTicketToUrl($adminurl . '?action=deletedone');
        $res = intval($res);
        header("X-JSON: ({'deleteurl' : '$deleteurl', 'result' : $res})");
    }
    
    
    

    function _actionHelp()
    {
        return;
    }
    
 
}

$pAdmin = new PluginAdminAuthorinfo('Authorinfo');
$pAdmin->doAction();
