<?php

namespace Codebuster\GptBundle\Classes;

use Contao\Config;
use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\Controller;
use Contao\StringUtil;
use Contao\System;
use Exception;

class GptClass
{
    public const CONTENT_FIELDS = [
        'headline',
        'sectionHeadline',
        'text',
        'html',
        'unfilteredHtml',
        'code',
        'listitems',
        'data',
        'tableitems',
        'summary',
        'mooHeadline',
        'alt',
        'imageTitle',
        'caption',
        'linkTitle',
        'titleText',
        'playerCaption',
    ];

    private static function prepareContent($objArticles): string
    {
        $content = [];
        $visitedContentElements = [];
        $customFields = StringUtil::deserialize(Config::get('gpt_custom_fields'), true);
        $fields = array_unique(array_merge(self::CONTENT_FIELDS, $customFields));

        Controller::loadDataContainer('tl_content');

        // get Content from all Articles
        if ($objArticles !== null) {
            foreach ($objArticles as $article) {
                self::appendContentElements($article, $fields, $content, $visitedContentElements);
            }
        }

        return implode("\n", $content);
    }

    /**
     * Gets content by given table and id
     * 
     * @param string $table
     * @param int $id
     * @throws Exception If content isn't found
     */
    public static function getContent($table, $id): string
    {

        //gets correct article of page
        if ($table == 'tl_page') {
            $articles = ArticleModel::findByPid($id);
            $ids = [];

            if ($articles !== null) {
                foreach ($articles as $v) {
                    $ids[] = $v->id;
                }
            }

            $table = "tl_article";
        }

        if (empty($ids)) {
            $ids[] = $id;
        }

        return self::prepareContent(self::getArticle($table, $ids));
    }

    /**
     * Checks if table is allowed to be accessed
     * in GPT settings
     * 
     * @param string $table
     * @return bool
     */
    protected static function isValidTable($table)
    {
        $tables = StringUtil::deserialize(Config::get('gpt_allowed_tables'), true);

        if ($tables === []) {
            $tables = ['tl_article'];
        }

        return in_array($table, $tables, true);
    }

    /**
     * Fetches Article of given table
     * 
     * @param string $table
     * @param array $ids
     * @throws Exception If content isn't found
     */
    public static function getArticle(string $table, array $ids)
    {


        //is table valid?
        if (\Contao\Database::getInstance()->tableExists($table) && self::isValidTable($table)) {


            $includeHidden = (bool) Config::get('gpt_hidden_elements');

            $objArticles = [];
            foreach ($ids as $id) {
                $model = $GLOBALS['TL_MODELS'][$table];
                $record = $includeHidden
                    ? $model::findByPk($id)
                    : $model::findBy(["id=?", "published=?"], [$id, 1]);

                if ($record) {
                    // get contentelements from article
                    $objArticles[] = self::findContentElements((int) $id, $table, $includeHidden);
                }
            }


            return $objArticles;

        } else {
            throw new Exception("Table not found. Check $table exists and has been approved in the settings.");
        }
    }

    /**
     * Find specific field in palette, so we don't send data that is not displayed in frontend.
     * @param string $palette
     * @param string $field
     * @return bool
     */
    private static function findFieldInPalette(string $palette, string $field): bool
    {
        return (bool) preg_match('/\b' . preg_quote($field, '/') . '\b/', $palette);
    }

    private static function getActivePalette($contentElement): string
    {
        $palettes = $GLOBALS['TL_DCA']['tl_content']['palettes'] ?? [];
        $subpalettes = $GLOBALS['TL_DCA']['tl_content']['subpalettes'] ?? [];
        $selectors = $palettes['__selector__'] ?? [];
        $palette = $palettes[$contentElement->type] ?? '';
        $loadedSubpalettes = [];

        do {
            $paletteChanged = false;

            foreach ($selectors as $selector) {
                if (isset($loadedSubpalettes[$selector]) || !self::findFieldInPalette($palette, $selector)) {
                    continue;
                }

                $value = $contentElement->$selector;

                if ($value === null || $value === '' || $value === false || $value === '0' || $value === 0) {
                    continue;
                }

                $valueKey = $selector . '_' . (string) $value;
                $subpaletteKey = isset($subpalettes[$valueKey]) ? $valueKey : $selector;

                if (!isset($subpalettes[$subpaletteKey])) {
                    continue;
                }

                $palette .= ',' . $subpalettes[$subpaletteKey];
                $loadedSubpalettes[$selector] = true;
                $paletteChanged = true;
            }
        } while ($paletteChanged);

        return $palette;
    }

    private static function appendContentElements($elements, array $fields, array &$content, array &$visited): void
    {
        if ($elements === null) {
            return;
        }

        foreach ($elements as $contentElement) {
            $contentElementId = (int) ($contentElement->id ?? 0);

            if ($contentElementId > 0) {
                if (isset($visited[$contentElementId])) {
                    continue;
                }

                $visited[$contentElementId] = true;
            }

            if ($contentElement->type !== 'module') {
                $palette = self::getActivePalette($contentElement);

                foreach ($fields as $field) {
                    if (!is_string($field) || $field === '' || !self::findFieldInPalette($palette, $field)) {
                        continue;
                    }

                    $definition = $GLOBALS['TL_DCA']['tl_content']['fields'][$field] ?? [];
                    $value = null;

                    if (($definition['inputType'] ?? '') === 'group') {
                        $container = System::getContainer();

                        if ($container->has(GroupWidgetContentExtractor::class)) {
                            $value = $container->get(GroupWidgetContentExtractor::class)->extract(
                                'tl_content',
                                $contentElementId,
                                $field
                            );
                        }
                    }

                    if ($value === null) {
                        $value = ContentValueExtractor::extract($contentElement->$field);
                    }

                    if ($value !== '') {
                        $content[] = $value;
                    }
                }
            }

            if ($contentElementId > 0) {
                $children = self::findContentElements(
                    $contentElementId,
                    'tl_content',
                    (bool) Config::get('gpt_hidden_elements')
                );

                self::appendContentElements($children, $fields, $content, $visited);
            }
        }
    }

    private static function findContentElements(int $parentId, string $parentTable, bool $includeHidden)
    {
        if ($includeHidden) {
            return ContentModel::findBy(
                ['pid=?', 'ptable=?'],
                [$parentId, $parentTable],
                ['order' => 'sorting']
            );
        }

        return ContentModel::findPublishedByPidAndTable($parentId, $parentTable);
    }
}
