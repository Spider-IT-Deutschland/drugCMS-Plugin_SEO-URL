<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Includes class to create websafe names
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend plugins
 * @version    0.1
 * @author     Stefan Seifarth / stese
 * @author     Murat Purc <murat@purc.de>
 * @copyright  � www.polycoder.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since Contenido release 4.8.15
 *
 * {@internal
 *   created   2004-12-04
 *   modified  2005-12-28
 *   modified  2011-05-19  Murat Purc, fixed wrong code in function recreateArticlesAliases()
 *
 *   $Id$:
 * }}
 *
 */


defined('CON_FRAMEWORK') or die('Illegal call');


/**
 * Class to create websafe names
 *
 * @author      Stefan Seifarth / stese
 * @author      Murat Purc <murat@purc.de>
 * @package     Contenido Backend plugins
 * @subpackage  ModRewrite
 */
class ModRewrite extends ModRewriteBase
{

    /**
     * Database instance
     *
     * @var  DB_Contenido
     */
    private static $_db;


    /**
     * Initialization, is to call at least once, also possible to call multible
     * times, if different client configuration is to load.
     *
     * Loads configuration of passed client and sets some properties.
     *
     * @param  int  $clientId  Client id
     */
    public static function initialize($clientId)
    {
        mr_loadConfiguration($clientId, true);
        self::$_db = new DB_Contenido();
    }


    /**
     * Check categories on websafe name
     *
     * Check all categories in the main parent category on existing same websafe name
     *
     * @param   string  $sName    Websafe name to check
     * @param   int     $iCatId   Current category id
     * @param   int     $iLangId  Current language id
     * @return     bool    True if websafename already exists, false if not
     */
    public static function isInCategories($sName = '', $iCatId = 0, $iLangId = 0)
    {
        global $cfg;

        $sName   = self::$_db->escape($sName);
        $iCatId  = (int) $iCatId;
        $iLangId = (int) $iLangId;

        // get parentid
        $iParentId = 0;
        $sql = "SELECT parentid FROM " . $cfg['tab']['cat'] . " WHERE idcat = '$iCatId'";
        if ($aData = mr_queryAndNextRecord($sql)) {
            $iParentId = ($aData['parentid'] > 0 ) ? (int) $aData['parentid'] : 0;
        }

        // check if websafe name is in this category
        $sql = "SELECT count(cl.idcat) as numcats FROM " . $cfg['tab']['cat_lang'] . " cl "
             . "LEFT JOIN " . $cfg['tab']['cat'] . " c ON cl.idcat = c.idcat WHERE "
             . "c.parentid = '$iParentId' AND cl.idlang = '" . $iLangId . "' AND "
             . "cl.urlname = '" . $sName . "' AND cl.idcat <> '$iCatId'";
        ModRewriteDebugger::log($sql, 'ModRewrite::isInCategories $sql');

        if ($aData = mr_queryAndNextRecord($sql)) {
            return ($aData['numcats'] > 0) ? true : false;
        }

        return false;
    }


    /**
     * Check articles on websafe name.
     *
     * Check all articles in the current category on existing same websafe name.
     *
     * @param    string  $sName    Websafe name to check
     * @param    int     $iArtId   Current article id
     * @param    int     $iLangId  Current language id
     * @param   int     $iCatId   Category id
     * @return     bool    True if websafename already exists, false if not
     */
    public static function isInCatArticles($sName = '', $iArtId = 0, $iLangId = 0, $iCatId = 0)
    {
        global $cfg;

        $sName   = self::$_db->escape($sName);
        $iArtId  = (int) $iArtId;
        $iLangId = (int) $iLangId;
        $iCatId  = (int) $iCatId;

        // handle multipages
        if ($iCatId == 0) {
            // get category id if not set
            $sql = "SELECT idcat FROM " . $cfg['tab']['cat_art'] . " WHERE idart = '$iArtId'";
            if ($aData = mr_queryAndNextRecord($sql)) {
                $iCatId = ($aData['idcat'] > 0) ? (int) $aData['idcat'] : 0;
            }
        }

        // check if websafe name is in this category
        $sql = "SELECT count(al.idart) as numcats FROM " . $cfg['tab']['art_lang'] . " al "
             . "LEFT JOIN " . $cfg['tab']['cat_art'] . " ca ON al.idart = ca.idart WHERE "
             . " ca.idcat='$iCatId' AND al.idlang='" . $iLangId . "' AND "
             . "al.urlname='" . $sName . "' AND al.idart <> '$iArtId'";
        if ($aData = mr_queryAndNextRecord($sql)) {
            return ($aData['numcats'] > 0) ? true : false;
        }

        return false;
    }


    /**
     * Set websafe name in article list.
     *
     * Insert new websafe name in article list
     *
     * @param   string  $sName    Original name (will be converted)
     * @param   int     $iArtId   Current article id
     * @param   int     $iLangId  Current language id
     * @param   int     $iCatId   Category id
     * @return     bool    True if insert was successfully
     */
    public static function setArtWebsafeName($sName = '', $iArtId = 0, $iLangId = 0, $iCatId = 0)
    {
        global $cfg;

        $iArtId  = (int) $iArtId;
        $iLangId = (int) $iLangId;
        $iCatId  = (int) $iCatId;

        // get websafe name
        $sNewName = capiStrCleanURLCharacters(html_entity_decode($sName));

        // remove double or more separators
        $sNewName = mr_removeMultipleChars('-', $sNewName);

        $sNewName = self::$_db->escape($sNewName);

        // check if websafe name already exists
        if (self::isInCatArticles($sNewName, $iArtId, $iLangId, $iCatId)) {
            // create new websafe name if exists
            $sNewName = $sNewName . $iArtId;
        }

        // check again - and set name
        if (!self::isInCatArticles($sNewName, $iArtId, $iLangId, $iCatId)) {
            // insert websafe name in article list
            $sql = "UPDATE " . $cfg['tab']['art_lang'] . " SET urlname = '$sNewName' "
                 . "WHERE idart = '$iArtId' AND idlang = '$iLangId'";
            return self::$_db->query($sql);
        } else {
            return false;
        }
    }


    /**
     * Set websafe name in category list.
     *
     * Insert new websafe name in category list.
     *
     * @param   string  $sName    Original name (will be converted) or alias
     * @param   int     $iCatId   Category id
     * @param   int     $iLangId  Language id
     * @return  bool    True if insert was successfully
     */
    public static function setCatWebsafeName($sName = '', $iCatId = 0, $iLangId = 0)
    {
        global $cfg;

        $iCatId  = (int) $iCatId;
        $iLangId = (int) $iLangId;

        // create websafe name
        $sNewName = capiStrCleanURLCharacters(html_entity_decode($sName));

        // remove double or more separators
        $sNewName = mr_removeMultipleChars('-', $sNewName);

        $sNewName = self::$_db->escape($sNewName);

        // check if websafe name already exists
        if (self::isInCategories($sNewName, $iCatId, $iLangId)) {
            // create new websafe name if exists
            $sNewName = $sNewName . $iCatId;
        }

        // check again - and set name
        if (!self::isInCategories($sNewName, $iCatId, $iLangId)) {
            // insert websafe name in article list
            $sql = "UPDATE " . $cfg['tab']['cat_lang'] . " SET urlname = '$sNewName' "
                 . "WHERE idcat = '$iCatId' AND idlang = '$iLangId'";

            ModRewriteDebugger::log(array(
                'sName'    => $sName,
                'iCatId'   => $iCatId,
                'iLangId'  => $iLangId,
                'sNewName' => $sNewName
                ), 'ModRewrite::setCatWebsafeName $data'
            );

            return self::$_db->query($sql);

        } else {
            return false;
        }
    }


    /**
     * Set urlpath of category
     *
     * @param   int     $iCatId   Category id
     * @param   int     $iLangId  Language id
     * @return  bool    True if insert was successfully
     */
    public static function setCatUrlPath($iCatId = 0, $iLangId = 0)
    {
        global $cfg;

        $sPath = self::buildRecursivPath($iCatId, $iLangId);

        $iCatId  = (int) $iCatId;
        $iLangId = (int) $iLangId;

        // insert websafe name in article list
        $sql = "UPDATE " . $cfg['tab']['cat_lang'] . " SET urlpath = '$sPath' "
             . "WHERE idcat = '$iCatId' AND idlang = '$iLangId'";

        ModRewriteDebugger::log(array(
            'iCatId'   => $iCatId,
            'iLangId'  => $iLangId,
            'sPath'    => $sPath
            ), 'ModRewrite::setCatUrlPath $data'
        );

        return self::$_db->query($sql);
    }


    /**
     * Get article id and language id from article language id
     *
     * @param   int    $iArtlangId  Current article id
     * @return  array  Array with idart and idlang of current article
     */
    public static function getArtIdByArtlangId($iArtlangId = 0)
    {
        global $cfg;

        $iArtlangId = (int) $iArtlangId;
        $sql = "SELECT idart, idlang FROM " . $cfg['tab']['art_lang'] . " WHERE idartlang = '$iArtlangId'";
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData;
        }
        return array();
    }


    /**
     * Get article id by article websafe name
     *
     * @param   string    Websafe name
     * @param   int       Category id
     * @return  int|null  Recent article id or null
     */
    public static function getArtIdByWebsafeName($sArtName = '', $iCatId = 0, $iLangId = 0)
    {
        global $cfg;

        $sArtName = self::$_db->escape($sArtName);
        $iCatId   = (int) $iCatId;
        $iLangId  = (int) $iLangId;

        $sWhere = '';
        if ($iLangId !== 0) {
            $sWhere = ' AND al.idlang=' . $iLangId;
        }
        // only article name were given
        if ($iCatId == 0) {
            // get all basic category ids with parentid=0
            $aCatIds = array();
            $sql = "SELECT idcat FROM " . $cfg['tab']['cat'] . " WHERE parentid = '0'";
            self::$_db->query($sql);
            while (self::$_db->next_record()) {
                $aCatIds[] = "idcat = '" . self::$_db->f('idcat') . "'";
            }
            $sWhere .= " AND ( " . join(" OR ", $aCatIds) . ")";
        } else {
            $sWhere .= " AND ca.idcat = '$iCatId'";
        }

        $sql = "SELECT al.idart FROM " . $cfg['tab']['art_lang'] . " al "
             . "LEFT JOIN " . $cfg['tab']['cat_art'] . " ca ON al.idart = ca.idart "
             . "WHERE al.urlname = '$sArtName'" . $sWhere;

        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData['idart'];
        } else {
            return null;
        }
    }


    /**
     * Get category name from category id and language id.
     *
     * @param   int     $iCatId   Category id
     * @param   int     $iLangId  Language id
     * @return  string  Category name
     */
    public static function getCatName($iCatId = 0, $iLangId = 0)
    {
        global $cfg, $mr_statics;

        $iCatId  = (int) $iCatId;
        $iLangId = (int) $iLangId;
        $key     = 'catname_by_catid_idlang_' . $iCatId . '_' . $iLangId;

        if (isset($mr_statics[$key])) {
            return $mr_statics[$key];
        }

        $sql = "SELECT name FROM " . $cfg['tab']['cat_lang'] . " WHERE idcat = '$iCatId' AND idlang = '$iLangId'";
        if ($aData = mr_queryAndNextRecord($sql)) {
            $catName = $aData['name'];
        } else {
            $catName = '';
        }

        $mr_statics[$key] = $catName;
        return $catName;
    }


    /**
     * Funcion to return cat id by path.
     *
     * Caches the paths at first call to provode faster processing at further calls.
     *
     * @todo add support for more than one hit for idcat, happens if u have same name for different idcats
     *          maybe one can check for on or offline cat or redirect in start-article 
     * @param   string  $path  Category path
     * @return  int  Category id
     */
    public static function getCatIdByUrlPath($path)
    {
        global $cfg, $client, $lang, $mr_statics;

        if (strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }
        if (strrpos($path, '/') === strlen($path)-1) {
            $path = substr($path, 0, -1);
        }

        $catSeperator = '/';
        $startFromRoot = parent::getConfig('startfromroot');
        $urls2lowercase = parent::getConfig('use_lowercase_uri');

        $path = str_replace('/', parent::getConfig('category_seperator'), $path);

        $key = 'cat_ids_and_urlpath';

        if (isset($mr_statics[$key])) {
            $aPathsCache = $mr_statics[$key];
        } else {
            $aPathsCache = array();
        }

        if (count($aPathsCache) == 0) {
            $sql = "SELECT cl.idcat, cl.urlpath FROM " . $cfg['tab']['cat_lang']
                 . " AS cl, " . $cfg['tab']['cat'] . " AS c WHERE c.idclient = " . $client
                 . " AND c.idcat = cl.idcat AND cl.idlang = " . $lang;
            self::$_db->query($sql);
            while (self::$_db->next_record()) {
                $urlPath = self::$_db->f('urlpath');
                if ($startFromRoot == 0 && strpos($urlPath, $catSeperator) > 0) {
                    // paths are stored with prefixed main category, but created
                    // urls doesn't contain the main cat, remove it...
                    $urlPath = substr($urlPath, strpos($urlPath, $catSeperator)+1);
                }
                if ($urls2lowercase) {
                    $urlPath = strtolower($urlPath);
                }

                // store path
                $aPathsCache[self::$_db->f('idcat')] = $urlPath;
            }
        }
        $mr_statics[$key] = $aPathsCache;

        // compare paths using the similar_text algorithm
        $fPercent = 0;
        $aResults = array();
        foreach ($aPathsCache as $id => $pathItem) {
            similar_text($path, $pathItem, $fPercent);
            $aResults[$id] = $fPercent;
        }

        arsort($aResults, SORT_NUMERIC);
        reset($aResults);

        ModRewriteDebugger::add($path, 'ModRewrite::getCatIdByUrlPath() $path');
        ModRewriteDebugger::add($aPathsCache, 'ModRewrite::getCatIdByUrlPath() $aPathsCache');
        ModRewriteDebugger::add($aResults, 'ModRewrite::getCatIdByUrlPath() $aResults');

        $iMinPercentage = (int) parent::getConfig('category_resolve_min_percentage', 0);
        $catId = key($aResults);
        if ($iMinPercentage > 0 && $aResults[$catId] < $iMinPercentage) {
            return 0;
        } else {
            return $catId;
        }
    }


    /**
     * Get article name from article id and language id
     *
     * @NOTE: seems to be not used???
     *
     * @param   int     $iArtId   Article id
     * @param   int     $iLangId  Language id
     * @return  string  Article name
     */
    public static function getArtTitle($iArtId = 0, $iLangId = 0)
    {
        global $cfg;

        $iArtId  = (int) $iArtId;
        $iLangId = (int) $iLangId;

        $sql = "SELECT title FROM " . $cfg['tab']['art_lang'] . " WHERE "
             . "idart = '$iArtId' AND idlang = '$iLangId'";
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData['title'];
        }
        return '';
    }


    /**
     * Get language ids from category id
     *
     * @param   int    $iCatId  Category id
     * @return  array  Used language ids
     */
    public static function getCatLanguages($iCatId = 0)
    {
        global $cfg, $mr_statics;

        $iCatId = (int) $iCatId;
        $key    = 'cat_idlang_by_catid_' . $iCatId;

        if (isset($mr_statics[$key])) {
            return $mr_statics[$key];
        }

        $aLanguages = array();

        $sql = "SELECT idlang FROM " . $cfg['tab']['cat_lang'] . " WHERE idcat = '$iCatId'";
        self::$_db->query($sql);
        while (self::$_db->next_record()) {
            $aLanguages[] = self::$_db->f('idlang');
        }

        $mr_statics[$key] = $aLanguages;
        return $aLanguages;
    }


    /**
     * Get article urlname and language id
     *
     * @param   int    $iArtlangId  idartlang
     * @return  array  Urlname, idlang of empty array
     */
    public static function getArtIds($iArtlangId = 0)
    {
        global $cfg;

        $iArtlangId = (int) $iArtlangId;
        $sql = "SELECT urlname, idlang FROM " . $cfg['tab']['art_lang'] . " WHERE idartlang = '$iArtlangId'";
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData;
        }
        return array();
    }


    /**
     * Build a recursiv path for mod_rewrite rule like server directories
     * (dir1/dir2/dir3)
     *
     * @param   int     $iCatId   Latest category id
     * @param   int     $iLangId  Language id
     * @param   int     $iLastId  Last category id
     * @return     string    linkpath with correct uri
     */
    public static function buildRecursivPath($iCatId = 0, $iLangId = 0, $iLastId = 0)
    {
        global $cfg;

        $aDirectories  = array();
        $bFinish       = false;
        $iTmpCatId     = (int) $iCatId;
        $iLangId       = (int) $iLangId;
        $iLastId       = (int) $iLastId;

        while ($bFinish == false) {
            $sql = "SELECT cl.urlname, c.parentid FROM " . $cfg['tab']['cat_lang'] . " cl "
                 . "LEFT JOIN " . $cfg['tab']['cat'] . " c ON cl.idcat = c.idcat "
                 . "WHERE cl.idcat = '$iTmpCatId' AND cl.idlang = '$iLangId'";
            if ($aData = mr_queryAndNextRecord($sql)) {
                $aDirectories[] = $aData['urlname'];
                $iTmpCatId      = (int) $aData['parentid'];

                if ($aData['parentid'] == 0 || $aData['parentid'] == $iLastId) {
                    $bFinish = true;
                }
            } else {
                $bFinish = true;
            }
        }

        // reverse array entries and create directory string
        $sPath = join('/', array_reverse($aDirectories));

        return $sPath;
    }


    /**
     * Return full contenido url from single anchor
     *
     * @param   array   $aMatches [0] = complete anchor, [1] = pre arguments, [2] = anchor name, [3] = post arguments
     * @return  string  New anchor
     */
    public static function rewriteHtmlAnchor(array $aMatches = array())
    {
        global $artname, $sess, $idart, $idcat, $client, $lang;

        // set article name
        $sArtParam = '';
        if (isset($artname) && strlen($artname) > 0) {
            $sArtParam = '&idart=' . (int) $idart;
        }

        // check for additional parameter in url
        $aParamsToIgnore = array ('idcat', 'idart', 'lang', 'client', 'idcatart', 'changelang', 'changeclient', 'idartlang', 'parts', 'artname');
        $sOtherParams = '';

        if (isset($_GET) && count($_GET) > 0) {
            foreach ($_GET as $key => $value) {
                if (!in_array($key, $aParamsToIgnore) && strlen(trim($value)) > 0) {
                    $aNoAnchor     = explode('#', $value);
                    $sOtherParams .= '&' . urlencode(urldecode($key)) . '=' . urlencode(urldecode($value));
                }
            }
        }

        $url = $sess->url(
            'front_content.php?' . 'idcat=' . (int) $idcat . '&client=' . (int) $client .
            '&changelang=' . (int) $lang . $sArtParam . $sOtherParams . '#' . $aMatches[2]
        );

        $sNewUrl = '<a' . $aMatches[1] . 'href="' . $url . '"' . $aMatches[3] . '>';

        return $sNewUrl;
    }


    /**
     * Return full contenido url from single anchor
     *
     * @param   array   $aMatches [0] = complete anchor, [1] = pre arguments, [2] = anchor name, [3] = post arguments
     * @return  string  New anchor
     */
    public static function contenidoHtmlAnchor(array $aMatches = array(), $bXHTML = true)
    {
        global $artname, $sess;

        $aParams    = array();
        $sAmpersand = $bXHTML ? '&amp;' : '&';

        foreach ($_GET as $key => $value) {
            $aNoAnchor = explode('#', $value);
            $aParams[] = urlencode(urldecode($key)) . '=' . urlencode(urldecode($aNoAnchor[0]));
        }

        $url =  $sess->url( 'front_content.php?' . implode($sAmpersand, $aParams) . '#' . $aMatches[2]);
        $sNewUrl = '<a' . $aMatches[1] . 'href="' . $url . '"' . $aMatches[3] . '>';

        return $sNewUrl;
    }


    /**
     * Get article websafe name from article id and language id.
     *
     * @param    int     $iArtId   Article id
     * @param    int     $iLangId  Language id
     * @return     string    Article websafe name
     */
    public static function getArtWebsafeName($iArtId = 0, $iLangId = 0)
    {
        global $cfg;

        $iArtId  = (int) $iArtId;
        $iLangId = (int) $iLangId;
        $sql = "SELECT urlname FROM " . $cfg['tab']['art_lang'] . " WHERE "
             . "idart = '" . $iArtId . "' AND idlang = '" . $iLangId . "'";
        if ($aData = mr_queryAndNextRecord($sql)) {
            return urldecode($aData['urlname']);
        }
        return null;
    }


    /**
     * Get article websafe name from idartlang.
     *
     * @param    int     $iArtLangId  idartlang
     * @return     string    Article websafe name
     */
    public static function getArtLangWebsafeName($iArtLangId = 0)
    {
        global $cfg;

        $iArtLangId = (int) $iArtLangId;
        $sql = "SELECT urlname FROM " . $cfg['tab']['art_lang'] . " WHERE idartlang = '". $iArtLangId . "'";
        if ($aData = mr_queryAndNextRecord($sql)) {
            return urldecode($aData['urlname']);
        }
        return null;
    }


    /**
     * Get name of client by id.
     *
     * @param   int     $clientId  Client id
     * @return  string  Client name
     */
    public static function getClientName($clientId = 0)
    {
        global $cfg, $mr_statics;

        $clientId = (int) $clientId;
        $key = 'clientname_by_clientid_' . $clientId;

        if (isset($mr_statics[$key])) {
            return $mr_statics[$key];
        }

        $sql = "SELECT name FROM " . $cfg['tab']['clients'] . " WHERE idclient = '$clientId'";
        if ($aData = mr_queryAndNextRecord($sql)) {
            $clientName = $aData['name'];
        } else {
            $clientName = '';
        }

        $mr_statics[$key] = $clientName;
        return $clientName;
    }


    /**
     * Get client id from client name
     *
     * @param   string   Client name
     * @return  integer  Client id
     */
    public static function getClientId($sClientName = '')
    {
        global $cfg, $mr_statics;

        $sClientName = self::$_db->escape($sClientName);
        $key         = 'clientid_by_name_' . $sClientName;

        if (isset($mr_statics[$key])) {
            return $mr_statics[$key];
        }

        $sql = "SELECT idclient FROM " . $cfg['tab']['clients'] . " WHERE name = '" . urldecode($sClientName) . "'";
        if ($aData = mr_queryAndNextRecord($sql)) {
            $clientId = $aData['idclient'];
        } else {
            $clientId = false;
        }

        $mr_statics[$key] = $clientId;
        return $clientId;
    }


    /**
     * Returns name of language by id.
     *
     * @param   int     $languageId  Language id
     * @return  string  Lanuage name
     */
    public static function getLanguageName($languageId = 0)
    {
        global $cfg, $mr_statics;

        $languageId = (int) $languageId;
        $key        = 'languagename_by_id_' . $languageId;

        if (isset($mr_statics[$key])) {
            return $mr_statics[$key];
        }

        $sql = "SELECT name FROM " . $cfg['tab']['lang'] . " WHERE idlang = '$languageId'";
        if ($aData = mr_queryAndNextRecord($sql)) {
            $languageName = $aData['name'];
        } else {
            $languageName = '';
        }

        $mr_statics[$key] = $languageName;
        return $languageName;
    }


    /**
     * Returns code of language by id.
     *
     * @param   int     $languageId  Language id
     * @return  string  Lanuage code
     */
    public static function getLanguageCode($languageId = 0)
    {
        global $cfg, $mr_statics;

        $languageId = (int) $languageId;
        $key        = 'languagecode_by_id_' . $languageId;

        if (isset($mr_statics[$key])) {
            return $mr_statics[$key];
        }

        $sql = 'SELECT value
                FROM ' . $cfg['tab']['properties'] . '
                WHERE ((itemtype="idlang")
                   AND (type="language")
                   AND (name="code")
                   AND (itemid=' . $languageId . '))';
        if ($aData = mr_queryAndNextRecord($sql)) {
            $languageCode = $aData['value'];
        } else {
            $languageCode = '';
        }

        $mr_statics[$key] = $languageCode;
        return $languageCode;
    }


    /**
     * Get language id from language name thanks to Nicolas Dickinson for multi
     * Client/Language BugFix
     *
     * @param  string   $sLanguageName  Language name
     * @param  int      $iClientId      Client id
     * @return integer  Language id
     */
    public static function getLanguageId($sLanguageName = '', $iClientId = 1, $bUseCode = false)
    {
        global $cfg, $mr_statics;

        $sLanguageName = self::$_db->escape($sLanguageName);
        $iClientId     = (int) $iClientId;
        $key           = 'langid_by_langname_clientid_' . $sLanguageName . '_' . $iClientId;

        if (isset($mr_statics[$key])) {
            return $mr_statics[$key];
        }
        
        if ($bUseCode) {
            $sql = 'SELECT itemid
                    FROM ' . $cfg['tab']['properties'] . '
                    WHERE ((idclient=' . $iClientId . ')
                       AND (itemtype="idlang")
                       AND (type="language")
                       AND (name="code")
                       AND (value="' . urldecode($sLanguageName) . '"))';
            if ($aData = mr_queryAndNextRecord($sql)) {
                $languageId = $aData['itemid'];
            } else {
                $languageId = false;
            }
        } else {
            $sql  = "SELECT l.idlang FROM " . $cfg['tab']['lang'] . " as l "
                  . "LEFT JOIN " . $cfg['tab']['clients_lang'] . " AS cl ON l.idlang = cl.idlang "
                  . "WHERE cl.idclient = '". $iClientId . "' AND l.name = '" . urldecode($sLanguageName) . "'";
            if ($aData = mr_queryAndNextRecord($sql)) {
                $languageId = $aData['idlang'];
            } else {
                $languageId = false;
            }
        }
        
        $mr_statics[$key] = $languageId;
        return $languageId;
    }


    /**
     * Splits passed argument into scheme://host and path/query.
     *
     * Example:
     * input  = http://host/front_content.php?idcat=123
     * return = array('htmlpath' => 'http://host', 'url' => 'front_content.php?idcat=123')
     *
     * @param  string  $url  URL to split
     * @return array   Assoziative array including the two parts:
     *                 - array('htmlpath' => $path, 'url' => $url)
     */
    public static function getClientFullUrlParts($url)
    {
        global $cfg, $cfgClient;

        $clientPath = $cfgClient[$client]['path']['htmlpath'];

        if (stristr($url, $clientPath) !== false) {
            // url includes full html path (scheme host path, etc.)
            $url      = str_replace($clientPath, '', $url);
            $htmlPath = $clientPath;
            $aComp    = parse_url($htmlPath);

            // check if path matches to defined rootdir from mod_rewrite conf
            if (isset($aComp['path']) && $aComp['path'] !== parent::getConfig('rootdir')) {
                // replace not matching path agaings configured one
                // this will replace e. g. "http://host/cms/" against "http://host/"
                $htmlPath = str_replace($aComp['path'], parent::getConfig('rootdir'), $htmlPath);
                if (substr($htmlPath, strlen($htmlPath)-1) == '/') {
                    // remove last slash
                    $htmlPath = substr($htmlPath, 0, strlen($htmlPath)-1);
                }
            }
        } else {
            $htmlPath = '';
        }
        return array('htmlpath' => $htmlPath, 'url' => $url);
    }


    /**
     * Function to preclean a url.
     *
     * Removes absolute path declaration '/front_content.php' or relative path
     * definition to actual dir './front_content.php', ampersand entities '&amp;'
     * and returns a url like 'front_content.php?idart=12&idlang=1'
     *
     * @param   string  $url  Url to clean
     * @return  string  Cleaned Url
     */
    public static function urlPreClean($url)
    {
        // some preparation of different front_content.php occurence
        if (strpos($url, './front_content.php') === 0) {
            $url = str_replace('./front_content.php', 'front_content.php', $url);
        } elseif (strpos($url, '/front_content.php') === 0) {
            $url = str_replace('/front_content.php', 'front_content.php', $url);
        }
        $url = str_replace('&amp;', '&', $url);
        return $url;
    }


    /**
     * Method to reset all aliases in categories.
     *
     * Clears all urlname entries in cat_lang table, and sets the value for all
     * existing entries.
     *
     * @deprecated  see ModRewrite::recreateCategoriesAliases();
     */
    public static function resetCategoriesAliases()
    {
        self::recreateCategoriesAliases();
    }


    /**
     * Recreates all or only empty aliases in categories table.
     *
     * @param  bool  $bOnlyEmpty  Flag to reset only empty items
     */
    public static function recreateCategoriesAliases($bOnlyEmpty = false)
    {
        global $cfg;

        $db = new DB_Contenido();

        $aCats = array();

        // get all or only empty categories
        $sql = "SELECT name, idcat, idlang FROM " . $cfg['tab']['cat_lang'];
        if ($bOnlyEmpty === true) {
            $sql .= " WHERE urlname IS NULL OR urlname = '' OR urlpath IS NULL OR urlpath = ''";
        }

        $db->query($sql);
        while ($db->next_record()) {
            //set new alias
            self::setCatWebsafeName($db->f('name'), $db->f('idcat'), $db->f('idlang'));
            $aCats[] = array('idcat' => $db->f('idcat'), 'idlang' => $db->f('idlang'));
        }

        foreach ($aCats as $p => $item) {
            self::setCatUrlPath($item['idcat'], $item['idlang']);
        }

        unset($db, $aCats);
    }


    /**
     * Method to reset all aliases in articles.
     *
     * Clears all urlname entries in art_lang table, and sets the value for all
     * existing entries.
     *
     * @deprecated  see ModRewrite::recreateArticlesAliases();
     */
    public static function resetArticlesAliases()
    {
        self::recreateArticlesAliases();
    }


    /**
     * Recreates all or only empty urlname entries in art_lang table.
     *
     * @param  bool  $bOnlyEmpty  Flag to reset only empty items
     */
    public static function recreateArticlesAliases($bOnlyEmpty = false)
    {
        global $cfg;

        $db = new DB_Contenido();

        // get all or only empty articles
        $sql = "SELECT title, idart, idlang FROM " . $cfg['tab']['art_lang'];
        if ($bOnlyEmpty === true) {
            $sql .= " WHERE urlname IS NULL OR urlname = ''";
        }
        $db->query($sql);

        while ($db->next_record()) {
            //set new alias
            self::setArtWebsafeName($db->f('title'), $db->f('idart'), $db->f('idlang'));
        }

        unset ($db);
    }


    /**
     * Method to reset all aliases (categories and articles).
     *
     * Shortcut to recreateCategoriesAliases() and recreateArticlesAliases()
     */
    public static function resetAliases()
    {
        self::recreateCategoriesAliases();
        self::recreateArticlesAliases();
    }


    /**
     * Recreate all or only empty aliases (categories and articles).
     *
     * Shortcut to recreateCategoriesAliases() and recreateArticlesAliases()
     *
     * @param  bool  $bOnlyEmpty  Flag to reset only empty items
     */
    public static function recreateAliases($bOnlyEmpty = false)
    {
        self::recreateCategoriesAliases($bOnlyEmpty);
        self::recreateArticlesAliases($bOnlyEmpty);
    }


    /**
     * Used to postprocess resolved path
     *
     * Error site handling if category not found
     *
     * if percentage == 100 and there is no 100 percentage result value,
     * error site will be shown - can be adjust by user settings for
     * smooth similar effects - 80 to 95 will be best but have to check by user
     *
     * @deprecated Is no more used
     *
     * @param   array  $results  Pathresolver results array
     * @return  mixed  Categoryid or false
     */
    public static function getIdFromPathresolverResult($results)
    {
        $iMinPercentage = (int) parent::getConfig('category_resolve_min_percentage', 0);
        $catId = key($results);
        if ($iMinPercentage > 0 && $results[$catId] < $iMinPercentage) {
            return false;
        } else {
            return $catId;
        }
    }

    /**
     * Returns .htaccess related assoziative info array
     *
     * @return  array
     */
    public static function getHtaccessInfo()
    {
        global $cfg, $client, $cfgClient;

        $arr = array(
            'contenido_full_path' => str_replace('\\', '/', realpath($cfg['path']['contenido'] . '../') . '/'),
            'client_full_path'    => $cfgClient[$client]['path']['frontend'],
        );
        $arr['in_contenido_path'] = is_file($arr['contenido_full_path'] . '.htaccess');
        $arr['in_client_path']    = is_file($arr['client_full_path'] . '.htaccess');
        $arr['has_htaccess']      = ($arr['in_contenido_path'] || $arr['in_client_path']);

        return $arr;
    }
}
