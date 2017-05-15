/**
 * np_authinfo.js
 * 
 * @author  takab
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @since   2005/12/10
 * @version CSV: $Id: np_authorinfo.js,v 1.1 2006/05/22 07:39:51 takab Exp $
 */
function np_authorinfo_uploadimage(event, id)
{
    var d = $('iframDiv');
    var url = Action_Url + '?action=uploadform&mnumber=' + id;
    if (d) {
        Element.remove(d);
    }
    d = document.createElement('div');
    d.setAttribute('id', 'iframDiv');
    Element.setStyle(d, $H({
        backgroungColor : '#ffffff',
        position : 'absolute',
        left : '600px'
    }));
    var i = document.createElement('iframe');
    Element.setStyle(i, $H({
        backgroungColor : '#ffffff',
        width: '300px',
        height: '150px'
    }));
    i.setAttribute('id', 'uploadwindow');
    i.setAttribute('name', 'uploadwindow');
    d.appendChild(i);
    $('content').appendChild(d);
    i.setAttribute('src', url);
    var o = Position.cumulativeOffset(Event.element(event));
    Element.setStyle(d, $H({
        top: o[1] + 'px'
    }));
}

function np_authorinfo_deleteimage(event, id)
{
    if (confirm('削除しますか？')) {
        var pars = 'mnumber=' + id;
        var myAjax = new Ajax.Request(
            Delete_Url,
            { 
              method: 'get', 
              parameters: pars, 
              onComplete: function(xmlhttprequest, json) {
                  np_authorinfo_deletesuccess(id,json)
              }
            }
        );
    }
}

function np_authorinfo_uploadsuccess(id, imageurl)
{
    np_authorinfo_uploadclose();
    var i = $('authorimg' + id);
    if (i) {
        i.setAttribute('src', imageurl);
    }
}
function np_authorinfo_uploadfailure()
{
    np_authorinfo_uploadclose();
}

function np_authorinfo_deletesuccess(id, json)
{
    if (json.deleteurl) {
        Delete_Url = json.deleteurl;
    }
    if (json.result) {
	    var i = $('authorimg' + id);
    	if (i) {
	        i.setAttribute('src', Noimage_Url);
    	}
    }
}

function np_authorinfo_uploadclose()
{
    Element.hide('iframDiv');
}
