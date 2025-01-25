<?php

namespace Codebuster\GptBundle\Classes;

use Contao\Config;
use Exception;
use Contao\ArticleModel;
use Contao\StringUtil;
use Contao\Database;
use Contao\ContentModel;
use Codebuster\GptBundle\Models\ContentElementsModel;

class GptClass
{
    private static function prepareContent($objArticles): string
    {
        $strContent = '';
        $customFields = [];
        if (Config::get("gpt_custom_fields")) {
            $customFields = unserialize(Config::get("gpt_custom_fields"));
        }

        // get Content from all Articles
        if ($objArticles !== null) {
            foreach ($objArticles as $article) {

                foreach ($article as $contentElement) {

                    if ($contentElement->type != "module") {

                        $headline = unserialize(strip_tags(nl2br($contentElement->headline)));
                        if ($headline["value"]) {
                            $strContent .= strip_tags(trim(preg_replace('/\s+/', ' ', $headline["value"]))) . ' - ';
                        }
                        $strContent .= strip_tags(trim(preg_replace('/\s+/', ' ', $contentElement->text)));

                        if (!empty($customFields)) {
                            foreach ($customFields as $customField) {
                                // dont regard serialized content
                                if (!is_array(unserialize($contentElement->$customField))) {
                                    $strContent .= strip_tags(trim(preg_replace('/\s+/', ' ', $contentElement->$customField)));
                                }
                            }
                        }
                    }
                }
            }
        }

        // Todo: do max chars even smarter
        return $strContent;
    }

    /**
     * Gets content by given table and id
     * 
     * @param String $table
     * @param int $id
     * @return Object
     * @throws Exception If content isn't found
     */
    public static function getContent($table, $id): string
    {
        //gets correct article of page
        if ($table == 'tl_page') {
            $articles = ArticleModel::findByPid($id);
            
            foreach ($articles as $v) {
                $ids[] = $v->id;
            }

            $table = "tl_article";
        } else {
            $ids = [$id];
        }

        return self::prepareContent(self::getArticle($table, $ids));
    }

    /**
     * Checks if table is allowed to be accessed
     * in GPT settings
     * 
     * @param String $table
     * @return Boolean
     */
    protected static function isValidTable($table)
    {
        $tables = Config::get('gpt_allowed_tables');
        $tables = StringUtil::deserialize($tables);

        foreach ($tables as $k => $v) {
            if ($v == $table) {
                return true;
            }
        }
    }

    /**
     * Fetches Article of given table
     * 
     * @param String $table
     * @param array $id
     * @return Object
     * @throws Exception If content isn't found
     */
    public static function getArticle(string $table, array $ids)
    {
        //is table valid?
        if (\Contao\Database::getInstance()->tableExists($table) && self::isValidTable($table)) {

            $blnHidden = false;
            if (Config::get("gpt_hidden_elements") === true) {
                $blnHidden = true;
            }
            $objArticles = [];
            foreach ($ids as $id) {
                //is record valid?
                $record = $GLOBALS['TL_MODELS'][$table]::findBy(["id=?", "published=?"], [$id, $blnHidden ? 0 : 1]);
                
                if ($record) {
                    // get contentelements from article
                    $objArticles[] = ContentModel::findBy(["pid=?", 'ptable=?'], [$id, $table]);
                } else {
                    throw new Exception("Record with ID $id not found.");
                }
            }

            return $objArticles;
        } else {
            throw new Exception("Table not found. Check $table exists and has been approved in the settings.");
        }
    }
}