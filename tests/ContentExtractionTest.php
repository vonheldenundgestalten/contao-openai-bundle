<?php

namespace Contao {
    final class Config
    {
        public static $values = [];

        public static function get($key)
        {
            return self::$values[$key] ?? null;
        }
    }

    final class StringUtil
    {
        public static function deserialize($value, $forceArray = false)
        {
            $result = is_string($value) ? unserialize($value, ['allowed_classes' => false]) : $value;

            return $forceArray && !is_array($result) ? [$result] : $result;
        }
    }

    final class Controller
    {
        public static function loadDataContainer($table): void
        {
        }
    }

    final class ContentModel
    {
        public static $children = [];

        public static function findPublishedByPidAndTable($parentId, $parentTable)
        {
            return self::$children[$parentTable][$parentId] ?? null;
        }

        public static function findBy($columns, $values, $options = [])
        {
            return self::$children[$values[1]][$values[0]] ?? null;
        }
    }
}

namespace {
    use Codebuster\GptBundle\Classes\ContentValueExtractor;
    use Codebuster\GptBundle\Classes\GptClass;
    use Contao\Config;
    use Contao\ContentModel;

    require_once __DIR__ . '/../src/Classes/ContentValueExtractor.php';
    require_once __DIR__ . '/../src/Classes/GptClass.php';

    $failures = [];
    $assertSame = static function (string $expected, string $actual, string $case) use (&$failures): void {
        if ($expected !== $actual) {
            $failures[] = sprintf('%s: expected %s, got %s', $case, var_export($expected, true), var_export($actual, true));
        }
    };

    $assertSame(
        'A title',
        ContentValueExtractor::extract(serialize(['unit' => 'h2', 'value' => 'A <strong>title</strong>'])),
        'headline metadata'
    );
    $assertSame(
        'One Two & three 42',
        ContentValueExtractor::extract(serialize([['One', 'Two &amp; three'], [42]])),
        'nested list and table values'
    );
    $assertSame(
        'Plain rich text',
        ContentValueExtractor::extract(" <p>Plain\n rich&nbsp;text</p> "),
        'plain HTML value'
    );
    $assertSame(
        'Nested custom value',
        ContentValueExtractor::extract(serialize(['content' => serialize(['Nested', '<em>custom</em> value'])])),
        'nested serialized custom value'
    );

    Config::$values['gpt_custom_fields'] = serialize(['customContent', 'customCaption']);
    $GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'] = ['addImage', 'overwriteMeta'];
    $GLOBALS['TL_DCA']['tl_content']['palettes']['list'] = 'headline,listitems,customContent';
    $GLOBALS['TL_DCA']['tl_content']['palettes']['text'] = 'headline,text,addImage';
    $GLOBALS['TL_DCA']['tl_content']['palettes']['accordion'] = 'headline';
    $GLOBALS['TL_DCA']['tl_content']['subpalettes']['addImage'] = 'overwriteMeta';
    $GLOBALS['TL_DCA']['tl_content']['subpalettes']['overwriteMeta'] = 'customCaption';

    $element = new \stdClass();
    $element->type = 'list';
    $element->headline = serialize(['unit' => 'h3', 'value' => 'Page headline']);
    $element->listitems = serialize(['First item', 'Second item']);
    $element->customContent = serialize(['Custom', ['nested', 'copy']]);

    $elementWithSubpalette = new \stdClass();
    $elementWithSubpalette->type = 'text';
    $elementWithSubpalette->headline = serialize(['unit' => 'h2', 'value' => '']);
    $elementWithSubpalette->text = '';
    $elementWithSubpalette->addImage = true;
    $elementWithSubpalette->overwriteMeta = true;
    $elementWithSubpalette->customCaption = serialize(['Visible', 'subpalette content']);

    $accordion = new \stdClass();
    $accordion->id = 10;
    $accordion->type = 'accordion';
    $accordion->headline = serialize(['unit' => 'h2', 'value' => 'Accordion section']);

    $accordionChild = new \stdClass();
    $accordionChild->id = 11;
    $accordionChild->type = 'text';
    $accordionChild->headline = serialize(['unit' => 'h3', 'value' => '']);
    $accordionChild->text = '<p>Nested accordion body</p>';
    $accordionChild->addImage = false;

    ContentModel::$children['tl_content'][10] = [$accordionChild];

    $method = new \ReflectionMethod(GptClass::class, 'prepareContent');
    $method->setAccessible(true);

    $assertSame(
        "Page headline\nFirst item Second item\nCustom nested copy\nVisible subpalette content\nAccordion section\nNested accordion body",
        $method->invoke(null, [[$element, $elementWithSubpalette, $accordion]]),
        'built-in and configured content fields'
    );

    if ($failures !== []) {
        fwrite(STDERR, implode("\n", $failures) . "\n");
        exit(1);
    }

    fwrite(STDOUT, "Content extraction tests passed.\n");
}
