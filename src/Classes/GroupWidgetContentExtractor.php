<?php

namespace Codebuster\GptBundle\Classes;

use Throwable;

final class GroupWidgetContentExtractor
{
    private $registry;

    public function __construct($registry = null)
    {
        $this->registry = $registry;
    }

    public function extract(string $table, int $rowId, string $field): ?string
    {
        if ($rowId < 1 || $this->registry === null) {
            return null;
        }

        try {
            $group = $this->registry->getGroup($table, $rowId, $field);
            $parts = [];

            foreach ($group->getElements() as $elementId) {
                foreach ($group->getFields() as $groupField) {
                    $definition = $group->getFieldDefinition($groupField) ?? [];

                    // File trees contain UUIDs rather than usable page copy.
                    if (($definition['inputType'] ?? '') === 'fileTree') {
                        continue;
                    }

                    $value = ContentValueExtractor::extract($group->getField($elementId, $groupField));

                    if ($value !== '') {
                        $parts[] = $value;
                    }
                }
            }

            return implode(' ', $parts);
        } catch (Throwable) {
            // Keep the integration optional and fall back to the stored field value.
            return null;
        }
    }
}
